from django.shortcuts import render
from django.db.models import Q

from news.models import News
from activities.models import Activity
from services.models import Service
from downloads.models import DownloadFile
from faq.models import FAQ
from departements.models import Departement
from cities.models import Cite
from homepage.models import Slider, DirectorWord, DirectorateHistory, OrganizationStructure


def normalize_arabic_search(text):
    """تطبيع النص العربي للبحث الأفضل"""
    if not text:
        return []
    
    text = text.strip()
    stop_words = ['في', 'من', 'إلى', 'على', 'عن', 'هذا', 'هذه', 'ذلك', 'تلك', 'أن', 'أو', 'لا', 'ما', 'هل']
    words = text.split()
    filtered_words = [w for w in words if w not in stop_words and len(w) >= 2]
    
    expanded_words = []
    for word in filtered_words:
        expanded_words.append(word)
        if word.startswith('ال') and len(word) > 2:
            without_al = word[2:]
            if without_al:
                expanded_words.append(without_al)
    
    return list(set(expanded_words))


def build_search_query(fields, search_terms):
    """بناء استعلام بحث ذكي"""
    query = Q()
    for term in search_terms:
        term_query = Q()
        for field in fields:
            term_query |= Q(**{f"{field}__icontains": term})
        query |= term_query
    return query


def search_view(request):
    """البحث الشامل الذكي"""
    
    query = request.GET.get('q', '').strip()
    search_type = request.GET.get('type', 'all')
    
    results = {
        'news': [],
        'activities': [],
        'services': [],
        'downloads': [],
        'faqs': [],
        'departements': [],
        'cites': [],
        'sliders': [],
        'director_words': [],
        'history': [],
        'organization': [],
    }
    
    counts = {
        'total': 0,
        'news': 0,
        'activities': 0,
        'services': 0,
        'downloads': 0,
        'faqs': 0,
        'departements': 0,
        'cites': 0,
        'sliders': 0,
        'director_words': 0,
        'history': 0,
        'organization': 0,
    }
    
    search_terms = []
    
    if query and len(query) >= 2:
        
        search_terms = normalize_arabic_search(query)
        
        print("=" * 60)
        print(f"🔍 البحث: '{query}'")
        print(f"📝 الكلمات: {search_terms}")
        print("=" * 60)
        
        # البحث في الأخبار
        if search_type in ['all', 'news']:
            news_query = build_search_query(
                ['title', 'content', 'summary'],
                search_terms
            )
            news_results = News.objects.filter(
                news_query,
                status='published'
            ).distinct().order_by('-published_date')[:30]
            results['news'] = news_results
            counts['news'] = news_results.count()
            print(f"📰 أخبار: {counts['news']}")
        
        # البحث في النشاطات
        if search_type in ['all', 'activities']:
            activity_query = build_search_query(
                ['title', 'description', 'short_description', 'location'],
                search_terms
            )
            activity_results = Activity.objects.filter(
                activity_query,
                is_active=True
            ).distinct().order_by('-date')[:30]
            results['activities'] = activity_results
            counts['activities'] = activity_results.count()
            print(f"📅 نشاطات: {counts['activities']}")
        
        # البحث في الخدمات
        if search_type in ['all', 'services']:
            service_query = build_search_query(
                ['title', 'short_description', 'detailed_description', 'requirements', 'how_to_apply'],
                search_terms
            )
            service_results = Service.objects.filter(
                service_query,
                is_active=True
            ).distinct().order_by('-created_at')[:30]
            results['services'] = service_results
            counts['services'] = service_results.count()
            print(f"🚌 خدمات: {counts['services']}")
        
        # البحث في الملفات
        if search_type in ['all', 'downloads']:
            download_query = build_search_query(
                ['title', 'description'],
                search_terms
            )
            download_results = DownloadFile.objects.filter(
                download_query,
                is_active=True
            ).distinct().order_by('-created_at')[:30]
            results['downloads'] = download_results
            counts['downloads'] = download_results.count()
            print(f"📥 ملفات: {counts['downloads']}")
        
        # البحث في الأسئلة
        if search_type in ['all', 'faqs']:
            faq_query = build_search_query(
                ['question', 'answer'],
                search_terms
            )
            faq_results = FAQ.objects.filter(
                faq_query,
                is_active=True
            ).distinct().order_by('-views_count')[:30]
            results['faqs'] = faq_results
            counts['faqs'] = faq_results.count()
            print(f"❓ أسئلة: {counts['faqs']}")
        
        # البحث في الأقسام
        if search_type in ['all', 'departements']:
            dept_query = build_search_query(
                ['name', 'description', 'head_of_department'],
                search_terms
            )
            dept_results = Departement.objects.filter(
                dept_query,
                is_active=True
            ).distinct()[:30]
            results['departements'] = dept_results
            counts['departements'] = dept_results.count()
            print(f"🏢 أقسام: {counts['departements']}")
        
        # البحث في الإقامات (مُصحح)
        if search_type in ['all', 'cites']:
            cite_query = build_search_query(
                ['name', 'address', 'description'],  # ← مُصحح: حذفنا 'city'
                search_terms
            )
            cite_results = Cite.objects.filter(
                cite_query,
                is_active=True
            ).distinct()[:30]
            results['cites'] = cite_results
            counts['cites'] = cite_results.count()
            print(f"🏛️ إقامات: {counts['cites']}")
        
        # البحث في السلايدر
        if search_type in ['all', 'sliders']:
            slider_query = build_search_query(
                ['title', 'subtitle'],
                search_terms
            )
            slider_results = Slider.objects.filter(
                slider_query,
                is_active=True
            ).distinct()[:20]
            results['sliders'] = slider_results
            counts['sliders'] = slider_results.count()
            print(f"🖼️ سلايدر: {counts['sliders']}")
        
        # البحث في كلمة المدير
        if search_type in ['all', 'director_words']:
            director_query = build_search_query(
                ['word_title', 'word_content', 'director_name', 'director_title'],
                search_terms
            )
            director_results = DirectorWord.objects.filter(
                director_query,
                is_active=True
            ).distinct()[:20]
            results['director_words'] = director_results
            counts['director_words'] = director_results.count()
            print(f"💬 كلمة المدير: {counts['director_words']}")
        
        # البحث في التاريخ
        if search_type in ['all', 'history']:
            history_query = build_search_query(
                ['title', 'introduction', 'content'],
                search_terms
            )
            history_results = DirectorateHistory.objects.filter(
                history_query,
                is_active=True
            ).distinct()[:20]
            results['history'] = history_results
            counts['history'] = history_results.count()
            print(f"📜 تاريخ: {counts['history']}")
        
        # البحث في الهيكل
        if search_type in ['all', 'organization']:
            org_query = build_search_query(
                ['title', 'description'],
                search_terms
            )
            org_results = OrganizationStructure.objects.filter(
                org_query,
                is_active=True
            ).distinct()[:20]
            results['organization'] = org_results
            counts['organization'] = org_results.count()
            print(f"🏗️ هيكل: {counts['organization']}")
        
        # المجموع
        counts['total'] = sum([
            counts['news'],
            counts['activities'],
            counts['services'],
            counts['downloads'],
            counts['faqs'],
            counts['departements'],
            counts['cites'],
            counts['sliders'],
            counts['director_words'],
            counts['history'],
            counts['organization'],
        ])
        
        print(f"📦 المجموع: {counts['total']}")
        print("=" * 60)
    
    context = {
        'query': query,
        'search_type': search_type,
        'results': results,
        'counts': counts,
        'page_title': f'نتائج البحث عن: {query}' if query else 'البحث',
        'search_terms': search_terms,
    }
    
    return render(request, 'search/search_results.html', context)