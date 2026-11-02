<?php
/**
 * موجّه الروابط (بديل urls.py في Django).
 *
 *   $r->get('/news/{slug}/', 'news_detail', 'news:news_detail');
 *   $r->post('/students/applications/{pk:int}/cancel/', 'student_cancel_application', 'students:cancel_application');
 *
 * {name}      => أي نص بدون "/"
 * {name:int}  => أرقام فقط
 */
class Router
{
    private array $routes = [];
    private array $named = [];

    public function add(array $methods, string $pattern, $handler, ?string $name = null): void
    {
        $regex = preg_replace_callback('/\{(\w+)(?::(\w+))?\}/', function ($m) {
            $type = $m[2] ?? '';
            $re = $type === 'int' ? '\d+' : '[^/]+';
            return '(?P<' . $m[1] . '>' . $re . ')';
        }, $pattern);
        $this->routes[] = [
            'methods' => $methods,
            'pattern' => $pattern,
            'regex'   => '#^' . $regex . '$#u',
            'handler' => $handler,
        ];
        if ($name) {
            $this->named[$name] = $pattern;
        }
    }

    public function get(string $pattern, $handler, ?string $name = null): void
    {
        $this->add(['GET', 'HEAD'], $pattern, $handler, $name);
    }

    public function post(string $pattern, $handler, ?string $name = null): void
    {
        $this->add(['POST'], $pattern, $handler, $name);
    }

    public function any(string $pattern, $handler, ?string $name = null): void
    {
        $this->add(['GET', 'HEAD', 'POST'], $pattern, $handler, $name);
    }

    /** إيجاد المسار المطابق وتنفيذه */
    public function dispatch(string $method, string $path): void
    {
        $allowed = [];
        foreach ($this->routes as $route) {
            if (!preg_match($route['regex'], $path, $m)) {
                continue;
            }
            if (!in_array($method, $route['methods'], true)) {
                $allowed = array_merge($allowed, $route['methods']);
                continue;
            }
            $params = [];
            foreach ($m as $k => $v) {
                if (is_string($k)) {
                    $params[] = rawurldecode($v);
                }
            }
            call_user_func_array($route['handler'], $params);
            return;
        }
        if ($allowed) {
            header('Allow: ' . implode(', ', array_unique($allowed)));
            abort(405);
        }
        // مثل APPEND_SLASH في Django: /news => /news/
        if (!str_ends_with($path, '/') && $method === 'GET') {
            foreach ($this->routes as $route) {
                if (preg_match($route['regex'], $path . '/')) {
                    $qs = $_SERVER['QUERY_STRING'] ?? '';
                    redirect(base_url() . route_prefix() . $path . '/' . ($qs !== '' ? '?' . $qs : ''), 301);
                }
            }
        }
        abort(404);
    }

    /** توليد رابط من اسم المسار (مثل {% url %} في Django) */
    public function url(string $name, array $params = []): string
    {
        if (!isset($this->named[$name])) {
            throw new InvalidArgumentException("المسار '$name' غير معرّف");
        }
        $i = 0;
        $path = preg_replace_callback('/\{(\w+)(?::\w+)?\}/', function ($m) use (&$i, $params) {
            $v = $params[$m[1]] ?? $params[$i] ?? '';
            $i++;
            return rawurlencode((string) $v);
        }, $this->named[$name]);
        return base_url() . route_prefix() . $path;
    }

    public function has(string $name): bool
    {
        return isset($this->named[$name]);
    }
}
