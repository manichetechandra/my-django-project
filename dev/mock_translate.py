#!/usr/bin/env python3
"""خادم وهمي يقلّد Gemini و MyMemory لاختبار app/core/Translator.php بلا إنترنت.

    python3 dev/mock_translate.py [port]          # الافتراضي 8999

ثم شغّل الموقع مع:
    TRANSLATE_PROVIDER=gemini GEMINI_API_KEY=dev-key \
    GEMINI_ENDPOINT=http://127.0.0.1:8999/v1beta \
    MYMEMORY_ENDPOINT=http://127.0.0.1:8999/get  php -S 0.0.0.0:8002 index.php

سلوك خاص للاختبار:
  - النموذج gemini-retired         → 404 (يختبر الرجوع إلى gemini-flash-latest)
  - المفتاح quota                   → 429 (يختبر الانتقال إلى MyMemory)
  - المفتاح ratelimit               → طلب من اثنين 429 مع retryDelay=1s (يختبر الانتظار في الترجمة الجماعية)
  - المفتاح bad                     → 400 API key not valid
  - q أطول من 500 بايت في MyMemory  → خطأ كما في الخدمة الحقيقية
الترجمة نفسها وهمية: قاموس صغير، وإلا يوضع [FR] أو [EN] قبل النص العربي.
"""
import json
import re
import sys
from http.server import BaseHTTPRequestHandler, ThreadingHTTPServer
from urllib.parse import parse_qs, urlparse

WORDS = {
    'fr': {'صرف المنحة': 'Versement de la bourse', 'المنحة': 'la bourse', 'الطلبة': 'les étudiants',
           'الإقامة': 'la résidence', 'سطيف': 'Sétif', 'خبر': 'actualité', 'تجريبي': 'de test'},
    'en': {'صرف المنحة': 'Scholarship payment', 'المنحة': 'the scholarship', 'الطلبة': 'students',
           'الإقامة': 'the residence', 'سطيف': 'Sétif', 'خبر': 'news', 'تجريبي': 'test'},
}
LOG = []


def fake(text, lang):
    out = text
    for ar, tr in sorted(WORDS[lang].items(), key=lambda kv: -len(kv[0])):
        out = out.replace(ar, tr)
    return out if out != text else '[' + lang.upper() + '] ' + text


class Handler(BaseHTTPRequestHandler):
    def log_message(self, fmt, *args):
        pass  # نطبع سطراً مختصراً لكل طلب بدل سجل الوصول الطويل

    def send_json(self, status, data):
        body = json.dumps(data, ensure_ascii=False).encode()
        self.send_response(status)
        self.send_header('Content-Type', 'application/json; charset=UTF-8')
        self.send_header('Content-Length', str(len(body)))
        self.end_headers()
        self.wfile.write(body)

    # ---------------------------------------------------------------- Gemini
    def do_POST(self):
        m = re.match(r'^/v1beta/models/([^/:]+):generateContent$', self.path)
        if not m:
            return self.send_json(404, {'error': {'code': 404, 'message': 'Not found', 'status': 'NOT_FOUND'}})
        model = m.group(1)
        key = self.headers.get('x-goog-api-key', '')
        req = json.loads(self.rfile.read(int(self.headers.get('Content-Length', 0))) or b'{}')
        print('mock: gemini model=%s key=%s prompt=%d chars' % (model, key, len(req['contents'][0]['parts'][0]['text']) if req.get('contents') else 0), flush=True)
        if model == 'gemini-retired':
            return self.send_json(404, {'error': {'code': 404, 'message': 'models/gemini-retired is not found for API version v1beta', 'status': 'NOT_FOUND'}})
        if key == 'ratelimit':  # طلب من اثنين يُرفض بحد الدقيقة مع RetryInfo (يختبر Translator::$patient)
            Handler.calls = getattr(Handler, 'calls', 0) + 1
            if Handler.calls % 2 == 1:
                return self.send_json(429, {'error': {'code': 429, 'message': 'Quota exceeded for metric: generate_content_free_tier_requests, limit: 10', 'status': 'RESOURCE_EXHAUSTED',
                                                      'details': [{'@type': 'type.googleapis.com/google.rpc.RetryInfo', 'retryDelay': '1s'}]}})
        if key == 'quota':
            return self.send_json(429, {'error': {'code': 429, 'message': 'You exceeded your current quota.', 'status': 'RESOURCE_EXHAUSTED'}})
        if key in ('', 'bad'):
            return self.send_json(400, {'error': {'code': 400, 'message': 'API key not valid. Please pass a valid API key.', 'status': 'INVALID_ARGUMENT'}})
        # التحقق من شكل الطلب كما تتوقعه الخدمة الحقيقية
        assert req['contents'][0]['role'] == 'user'
        assert req['generationConfig']['responseMimeType'] == 'application/json'
        assert 'Sétif' in req['systemInstruction']['parts'][0]['text']
        prompt = req['contents'][0]['parts'][0]['text']
        texts = json.loads(prompt[prompt.index('\n\n') + 2:])
        langs = re.findall(r'\(key "(\w+)"\)', prompt)
        result = {lang: {k: fake(v, lang) for k, v in texts.items()} for lang in langs}
        LOG.append(('gemini', model, list(texts)))
        self.send_json(200, {
            'candidates': [{'content': {'parts': [{'text': json.dumps(result, ensure_ascii=False, indent=2)}], 'role': 'model'},
                            'finishReason': 'STOP', 'index': 0}],
            'usageMetadata': {'promptTokenCount': len(prompt) // 4, 'candidatesTokenCount': 50, 'totalTokenCount': len(prompt) // 4 + 50},
            'modelVersion': model,
        })

    # ---------------------------------------------------------------- MyMemory
    def do_GET(self):
        u = urlparse(self.path)
        if u.path != '/get':
            return self.send_json(404, {'error': 'not found'})
        qs = parse_qs(u.query)
        q = qs.get('q', [''])[0]
        pair = qs.get('langpair', [''])[0]
        lang = pair.split('|')[-1]
        print('mock: mymemory %s %d bytes de=%s' % (pair, len(q.encode()), qs.get('de', ['-'])[0]), flush=True)
        if len(q.encode()) > 500:
            return self.send_json(200, {'responseData': {'translatedText': 'QUERY LENGTH LIMIT EXCEEDED. MAX ALLOWED QUERY : 500 CHARS', 'match': 0},
                                        'quotaFinished': False, 'responseDetails': 'QUERY LENGTH LIMIT EXCEEDED. MAX ALLOWED QUERY : 500 CHARS', 'responseStatus': 403})
        LOG.append(('mymemory', pair, len(q.encode())))
        self.send_json(200, {'responseData': {'translatedText': fake(q, lang).replace("'", '&#39;'), 'match': 0.85},
                             'quotaFinished': False, 'mtLangSupported': None, 'responseDetails': '', 'responseStatus': 200,
                             'responderId': None, 'exception_code': None, 'matches': []})


if __name__ == '__main__':
    port = int(sys.argv[1]) if len(sys.argv) > 1 else 8999
    print('mock translate server on 127.0.0.1:%d' % port, flush=True)
    ThreadingHTTPServer(('127.0.0.1', port), Handler).serve_forever()
