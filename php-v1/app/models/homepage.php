<?php
/**
 * نماذج الصفحة الرئيسية (homepage/models.py)
 */

class Slider extends Model
{
    protected static string $table = 'sliders';
    protected static array $booleans = ['is_active'];
    protected static array $defaults = ['subtitle' => '', 'button_text' => '', 'order' => 0, 'is_active' => 1];
    protected static array $createdFields = ['created_at'];
}

class Statistic extends Model
{
    protected static string $table = 'statistics';
    protected static array $booleans = ['is_active'];
    protected static array $defaults = ['order' => 0, 'is_active' => 1];
}

class DirectorWord extends Model
{
    protected static string $table = 'director_words';
    protected static array $booleans = ['is_active'];
    protected static array $defaults = ['director_title' => 'مدير الخدمات الجامعية', 'word_title' => 'كلمة المدير', 'is_active' => 1];
    protected static array $createdFields = ['created_at'];
    protected static array $updatedFields = ['updated_at'];
}

class OrganizationStructure extends Model
{
    protected static string $table = 'organization_structures';
    protected static array $booleans = ['is_active'];
    protected static array $defaults = ['title' => 'الهيكل التنظيمي', 'description' => '', 'content' => '', 'is_active' => 1];
    protected static array $updatedFields = ['updated_at'];
}

class DirectorateHistory extends Model
{
    protected static string $table = 'directorate_histories';
    protected static array $booleans = ['is_active'];
    protected static array $defaults = ['title' => 'تاريخ المديرية', 'is_active' => 1];
    protected static array $updatedFields = ['updated_at'];
}
