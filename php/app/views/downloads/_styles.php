<style>
    .downloads-header {
        background: linear-gradient(135deg, #2c3e50 0%, #8cc040 100%);
        color: white;
        padding: 60px 0;
        margin-bottom: 40px;
        margin-top: 0;
    }
    .stats-box {
        background: white;
        padding: 25px;
        border-radius: 10px;
        text-align: center;
        box-shadow: 0 3px 10px rgba(0,0,0,0.1);
        margin-bottom: 20px;
    }
    .stats-box i { font-size: 44px; color: #8cc040; margin-bottom: 12px; }
    .stats-box h3 { font-size: 40px; margin: 0; color: #2c3e50; font-weight: bold; }
    .stats-box p { margin: 8px 0 0; color: #666; }
    .search-box {
        background: white;
        padding: 20px;
        border-radius: 10px;
        box-shadow: 0 3px 10px rgba(0,0,0,0.1);
        margin-bottom: 30px;
    }
    .search-box input, .search-box select { border: 2px solid #ddd; border-radius: 5px; height: 46px; font-size: 15px; }
    .search-box input:focus, .search-box select:focus { border-color: #8cc040; box-shadow: none; }
    .category-section {
        background: white;
        border-radius: 10px;
        padding: 20px;
        margin-bottom: 30px;
        box-shadow: 0 2px 10px rgba(0,0,0,0.1);
    }
    .category-header {
        padding: 15px 20px;
        border-radius: 8px;
        color: white;
        margin-bottom: 20px;
        overflow: hidden;
    }
    .category-header h3 { margin: 0; font-size: 22px; display: inline; }
    .category-header .badge {
        background: rgba(255,255,255,0.3);
        padding: 5px 15px;
        border-radius: 20px;
        font-size: 14px;
        float: left; float: inline-end;
        margin-top: 3px;
    }
    .file-card {
        display: flex;
        align-items: center;
        gap: 15px;
        padding: 15px;
        border: 1px solid #eee;
        border-radius: 8px;
        margin-bottom: 12px;
        transition: all 0.3s;
        background: #fff;
    }
    .file-card:hover { box-shadow: 0 5px 15px rgba(0,0,0,0.1); transform: translateY(-2px); border-color: #8cc040; }
    .file-icon {
        flex-shrink: 0;
        width: 60px;
        height: 60px;
        border-radius: 10px;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 30px;
        color: white;
    }
    .file-info { flex: 1; min-width: 0; }
    .file-info h4 { margin: 0 0 6px; font-size: 16px; color: #2c3e50; font-weight: bold; }
    .file-info p { margin: 0 0 6px; color: #7f8c8d; font-size: 13px; }
    .file-meta { font-size: 12px; color: #95a5a6; }
    .file-meta span { margin-inline-end: 12px; white-space: nowrap; }
    /* الهاتف: اسم التصنيف الطويل (بالفرنسية مثلاً) ينزل إلى سطر ثانٍ بدل أن يخرج من الشاشة */
    @media (max-width: 767px) {
        .file-meta span { display: inline-block; max-width: 100%; white-space: normal; }
    }
    .btn-download { background: #8cc040; color: white; border: none; white-space: nowrap; }
    .btn-download:hover, .btn-download:focus { background: #7ab030; color: white; }
    .sidebar-box {
        background: white;
        border-radius: 10px;
        padding: 20px;
        margin-bottom: 25px;
        box-shadow: 0 2px 10px rgba(0,0,0,0.1);
    }
    .sidebar-box h4 { margin-top: 0; padding-bottom: 12px; border-bottom: 2px solid #8cc040; color: #2c3e50; font-weight: bold; }
    .category-list { list-style: none; padding: 0; margin: 0; }
    .category-list li a {
        display: block;
        padding: 10px 12px;
        border-radius: 6px;
        color: #2c3e50;
        text-decoration: none;
        transition: all 0.2s;
    }
    .category-list li a:hover, .category-list li a.active { background: #f0f7e6; color: #6fa832; }
    .category-dot { display: inline-block; width: 10px; height: 10px; border-radius: 50%; margin-inline-end: 8px; }
    .featured-badge { background: #f39c12; color: white; font-size: 11px; padding: 2px 8px; border-radius: 10px; margin-inline-start: 5px; }
    .empty-state { text-align: center; padding: 60px 20px; color: #95a5a6; }
    .empty-state i { font-size: 64px; margin-bottom: 15px; display: block; }
    @media (max-width: 767px) {
        .file-card { flex-wrap: wrap; }
        .file-card .btn-download { width: 100%; }
    }
</style>
