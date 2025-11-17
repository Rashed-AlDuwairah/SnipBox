<?php
declare(strict_types=1);

// =========================
// إعدادات عامة
// =========================

const CARDS_FILE = __DIR__ . '/cards.json';

// =========================
// دوال مساعدة بسيطة
// =========================

function e(string $value): string
{
    return htmlspecialchars($value, ENT_QUOTES, 'UTF-8');
}

function generateCardId(): string
{
    return bin2hex(random_bytes(8)); // مثال: a3f9c1d2e4b5ff11
}

// =========================
// كيان البطاقة (Entity)
// =========================

class Card
{
    public string $id;
    public string $name;
    public string $jobTitle;
    public ?string $bio;
    public string $email;
    public string $phone;
    public ?string $linkedin;
    public ?string $github;
    public string $theme;
    public string $createdAt;

    public function __construct(
        string $id,
        string $name,
        string $jobTitle,
        ?string $bio,
        string $email,
        string $phone,
        ?string $linkedin,
        ?string $github,
        string $theme,
        string $createdAt
    ) {
        $this->id        = $id;
        $this->name      = $name;
        $this->jobTitle  = $jobTitle;
        $this->bio       = $bio;
        $this->email     = $email;
        $this->phone     = $phone;
        $this->linkedin  = $linkedin;
        $this->github    = $github;
        $this->theme     = $theme;
        $this->createdAt = $createdAt;
    }

    public function toArray(): array
    {
        return [
            'id'         => $this->id,
            'name'       => $this->name,
            'job_title'  => $this->jobTitle,
            'bio'        => $this->bio,
            'email'      => $this->email,
            'phone'      => $this->phone,
            'linkedin'   => $this->linkedin,
            'github'     => $this->github,
            'theme'      => $this->theme,
            'created_at' => $this->createdAt,
        ];
    }

    public static function fromArray(array $data): self
    {
        return new self(
            $data['id'],
            $data['name'],
            $data['job_title'],
            $data['bio'] ?? null,
            $data['email'],
            $data['phone'],
            $data['linkedin'] ?? null,
            $data['github'] ?? null,
            $data['theme'] ?? 'modern',
            $data['created_at'] ?? date('c')
        );
    }
}

// =========================
// مستودع البطاقات (JSON Repository)
// =========================

class CardRepository
{
    public function __construct(
        private string $filePath = CARDS_FILE
    ) {}

    /**
     * @return Card[]
     */
    public function all(): array
    {
        if (!file_exists($this->filePath)) {
            return [];
        }

        $json = file_get_contents($this->filePath);
        if ($json === false || trim($json) === '') {
            return [];
        }

        $data = json_decode($json, true);
        if (!is_array($data)) {
            return [];
        }

        $cards = [];
        foreach ($data as $row) {
            if (!is_array($row)) {
                continue;
            }
            $cards[] = Card::fromArray($row);
        }

        return $cards;
    }

    public function add(Card $card): bool
    {
        $cards = $this->all();
        $cards[] = $card;

        $arrayData = array_map(
            fn(Card $c) => $c->toArray(),
            $cards
        );

        $json = json_encode(
            $arrayData,
            JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT
        );

        return file_put_contents($this->filePath, $json, LOCK_EX) !== false;
    }
}

// =========================
// Validator التحقق من البيانات
// =========================

class CardValidator
{
    /**
     * @param array $input $_POST
     * @return array [errors, cleaned]
     */
    public static function validate(array $input): array
    {
        $errors = [];
        $clean  = [];

        $fields = [
            'full_name',
            'job_title',
            'bio',
            'email',
            'phone',
            'linkedin',
            'github',
            'theme',
        ];

        foreach ($fields as $field) {
            $clean[$field] = isset($input[$field])
                ? trim((string)$input[$field])
                : '';
        }

        // الاسم الكامل
        if ($clean['full_name'] === '') {
            $errors['full_name'] = 'الاسم مطلوب';
        } elseif (mb_strlen($clean['full_name']) > 100) {
            $errors['full_name'] = 'الاسم طويل جدًا';
        }

        // المسمى الوظيفي
        if ($clean['job_title'] === '') {
            $errors['job_title'] = 'المسمى الوظيفي مطلوب';
        } elseif (mb_strlen($clean['job_title']) > 100) {
            $errors['job_title'] = 'المسمى الوظيفي طويل جدًا';
        }

        // النبذة (اختيارية)
        if ($clean['bio'] !== '' && mb_strlen($clean['bio']) > 300) {
            $errors['bio'] = 'النبذة يجب ألا تتجاوز 300 حرف.';
        }

        // البريد الإلكتروني
        if ($clean['email'] === '') {
            $errors['email'] = 'البريد الإلكتروني مطلوب';
        } elseif (!filter_var($clean['email'], FILTER_VALIDATE_EMAIL)) {
            $errors['email'] = 'صيغة البريد الإلكتروني غير صحيحة';
        }

        // رقم الجوال (تحقق بسيط)
        if ($clean['phone'] === '') {
            $errors['phone'] = 'رقم الجوال مطلوب';
        } elseif (!preg_match('/^[\d\s+\-]{6,20}$/u', $clean['phone'])) {
            $errors['phone'] = 'صيغة رقم الجوال غير صحيحة';
        }

        // الروابط الاجتماعية (LinkedIn / GitHub) اختيارية لكن إن وجدت يجب أن تبدأ بـ http/https
        foreach (['linkedin', 'github'] as $field) {
            if ($clean[$field] !== '' && !preg_match('~^https?://~i', $clean[$field])) {
                $errors[$field] = 'الرجاء إدخال رابط يبدأ بـ http أو https';
            }
        }

        // الثيم
        $allowedThemes = ['modern', 'professional', 'creative'];
        if (!in_array($clean['theme'], $allowedThemes, true)) {
            $clean['theme'] = 'modern';
        }

        return [$errors, $clean];
    }
}

// =========================
// Controller منطق الصفحة
// =========================

session_start();

$repo   = new CardRepository();
$errors = [];
$old    = [
    'full_name' => '',
    'job_title' => '',
    'bio'       => '',
    'email'     => '',
    'phone'     => '',
    'linkedin'  => '',
    'github'    => '',
    'theme'     => 'modern',
];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    [$errors, $clean] = CardValidator::validate($_POST);
    $old = array_merge($old, $clean);

    if (empty($errors)) {
        $card = new Card(
            generateCardId(),
            $clean['full_name'],
            $clean['job_title'],
            $clean['bio'] !== '' ? $clean['bio'] : null,
            $clean['email'],
            $clean['phone'],
            $clean['linkedin'] !== '' ? $clean['linkedin'] : null,
            $clean['github'] !== '' ? $clean['github'] : null,
            $clean['theme'],
            date('c')
        );
    
        if (!$repo->add($card)) {
            $errors['general'] = 'حدث خطأ أثناء حفظ الكرت، حاول مرة أخرى.';
        } else {
            // بدل التحويل — نخزن ID ونترك الصفحة تعرض الرسالة
            $successId = $card->id;
        }
    }
    
}

// =========================
// View: هنا تضع HTML + الفورم
// =========================
?>



<!doctype html>
<html lang="ar" dir="rtl">
 <head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>MyCard - إنشاء بطاقة العمل</title>
  <script src="https://cdn.tailwindcss.com"></script>
  <script src="/_sdk/element_sdk.js"></script>
  <link href="https://fonts.googleapis.com/css2?family=Cairo:wght@400;600;700&amp;display=swap" rel="stylesheet">
  <style>
        body { 
            box-sizing: border-box; 
            font-family: 'Cairo', sans-serif; 
        }
        
        /* خلفية متحركة */
        .gradient-bg {
            background: linear-gradient(-45deg, #e0e7ff, #f0f4ff, #f8fafc, #e2e8f0);
            background-size: 400% 400%;
            animation: gradientMove 15s ease infinite;
        }
        
        @keyframes gradientMove {
            0%, 100% { background-position: 0% 50%; }
            50% { background-position: 100% 50%; }
        }
        
        /* حركة الظهور */
        .fade-up {
            opacity: 0;
            transform: translateY(20px);
            animation: fadeUp 0.8s ease forwards;
        }
        
        @keyframes fadeUp {
            to { opacity: 1; transform: translateY(0); }
        }
        
        /* تأثيرات الحقول */
        .input-focus:focus {
            box-shadow: 0 0 0 3px rgba(99, 102, 241, 0.1);
            transform: translateY(-1px);
        }
        
        /* بطاقات الثيمات */
        .theme-card {
            transition: all 0.3s ease;
            cursor: pointer;
        }
        
        .theme-card:hover, .theme-card.selected {
            transform: translateY(-3px);
            box-shadow: 0 8px 25px rgba(0, 0, 0, 0.15);
        }
        
        .theme-card.selected {
            box-shadow: 0 0 0 3px rgba(99, 102, 241, 0.3);
        }
        
        /* معاينة عائمة */
        .floating {
            animation: float 3s ease-in-out infinite;
        }
        
        @keyframes float {
            0%, 100% { transform: translateY(0); }
            50% { transform: translateY(-8px); }
        }
        
        /* زر الإنشاء */
        .create-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 12px 30px rgba(99, 102, 241, 0.4);
        }
        
        /* رسالة النجاح */
        .success-msg {
            opacity: 0;
            transform: translateY(15px);
            transition: all 0.4s ease;
        }
        
        .success-msg.show {
            opacity: 1;
            transform: translateY(0);
        }
    </style>
  <style>@view-transition { navigation: auto; }</style>
  <script src="/_sdk/data_sdk.js" type="text/javascript"></script>
 </head>
 <body class="gradient-bg min-h-full text-gray-800"><!-- خلفية زخرفية -->
  <div class="absolute inset-0 overflow-hidden">
   <div class="absolute top-20 left-10 w-48 h-48 bg-indigo-300 rounded-full opacity-10 blur-3xl animate-pulse"></div>
   <div class="absolute top-40 right-10 w-48 h-48 bg-blue-300 rounded-full opacity-10 blur-3xl animate-pulse" style="animation-delay: 2s;"></div>
   <div class="absolute bottom-20 left-1/2 w-48 h-48 bg-purple-300 rounded-full opacity-10 blur-3xl animate-pulse" style="animation-delay: 4s;"></div>
  </div><!-- العنوان الرئيسي -->
  <section class="relative z-10 pt-16 pb-8 px-4">
   <div class="max-w-4xl mx-auto text-center fade-up">
    <h1 id="page-title" class="text-4xl lg:text-5xl font-bold mb-4 bg-gradient-to-r from-gray-700 to-indigo-600 bg-clip-text text-transparent">اصنع بطاقتك الآن</h1>
    <p id="page-subtitle" class="text-xl text-gray-600 mb-2">املأ بياناتك وابدأ بصناعة بطاقة عملك الرقمية</p>
    <p class="text-sm text-gray-500">أقل من دقيقة</p>
   </div>
  </section><!-- المحتوى الرئيسي -->
  <section class="relative z-10 py-8 px-4">
   <div class="max-w-6xl mx-auto">
    <div class="grid lg:grid-cols-3 gap-8"><!-- النموذج -->
     <div class="lg:col-span-2 space-y-6"><!-- بيانات شخصية -->
      <div class="bg-white rounded-3xl p-8 shadow-lg fade-up" style="animation-delay: 0.2s;">
       <h2 id="form-title" class="text-2xl font-bold text-gray-800 mb-6 flex items-center gap-3">
        <div class="w-8 h-8 bg-gradient-to-br from-indigo-500 to-purple-600 rounded-lg flex items-center justify-center">
         <svg class="w-4 h-4 text-white" fill="none" stroke="currentColor" viewbox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
         </svg>
        </div> بياناتك الشخصية</h2>


        <?php if (!empty($successId)): ?>
            <div 
                id="success-message"
                class="mb-6 flex items-center gap-3 px-4 py-3 rounded-2xl bg-green-100 text-green-700 font-semibold text-lg shadow-sm transition-all"
            >
                <svg xmlns="http://www.w3.org/2000/svg" class="w-6 h-6" fill="none"
                    viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round"
                        d="M5 13l4 4L19 7" />
                </svg>
                <span>تم إنشاء بطاقتك بنجاح! 🎉</span>
            </div>

            <script>
                // الانتظار 5 ثواني ثم التوجيه
                setTimeout(function() {
                    window.location.href = "card.php?id=<?= $successId ?>";
                }, 5000);
            </script>
        <?php endif; ?>


        <form
    id="card-form"
    class="space-y-6"
    method="post"
    action=""
>
    <!-- الاسم والوظيفة -->
    <div class="grid sm:grid-cols-2 gap-4">

        <div>
            <label class="block text-sm font-semibold text-gray-700 mb-2">
                الاسم الكامل *
            </label>
            <input
                type="text"
                id="full_name"
                name="full_name"
                required
                class="w-full px-4 py-3 border border-gray-200 rounded-2xl input-focus transition-all bg-gray-50 focus:bg-white"
                placeholder="محمد أحمد السعيد"
                value="<?= e($old['full_name'] ?? '') ?>"
            >
            <?php if (!empty($errors['full_name'])): ?>
                <p class="text-sm text-red-600 mt-1">
                    <?= e($errors['full_name']) ?>
                </p>
            <?php endif; ?>
        </div>

        <div>
            <label class="block text-sm font-semibold text-gray-700 mb-2">
                المسمى الوظيفي *
            </label>
            <input
                type="text"
                id="job_title"
                name="job_title"
                required
                class="w-full px-4 py-3 border border-gray-200 rounded-2xl input-focus transition-all bg-gray-50 focus:bg-white"
                placeholder="مطور ويب"
                value="<?= e($old['job_title'] ?? '') ?>"
            >
            <?php if (!empty($errors['job_title'])): ?>
                <p class="text-sm text-red-600 mt-1">
                    <?= e($errors['job_title']) ?>
                </p>
            <?php endif; ?>
        </div>

    </div>

    <!-- النبذة -->
    <div>
        <label class="block text-sm font-semibold text-gray-700 mb-2">
            نبذة قصيرة
        </label>
        <textarea
            id="bio"
            name="bio"
            rows="3"
            class="w-full px-4 py-3 border border-gray-200 rounded-2xl input-focus transition-all bg-gray-50 focus:bg-white resize-none"
            placeholder="شغوف بصناعة تجارب ويب حديثة ومبتكرة..."
        ><?= e($old['bio'] ?? '') ?></textarea>
        <?php if (!empty($errors['bio'])): ?>
            <p class="text-sm text-red-600 mt-1">
                <?= e($errors['bio']) ?>
            </p>
        <?php endif; ?>
    </div>

    <!-- معلومات التواصل -->
    <div class="grid sm:grid-cols-2 gap-4">

        <div>
            <label class="block text-sm font-semibold text-gray-700 mb-2">
                البريد الإلكتروني *
            </label>
            <input
                type="email"
                id="email"
                name="email"
                required
                class="w-full px-4 py-3 border border-gray-200 rounded-2xl input-focus transition-all bg-gray-50 focus:bg-white"
                placeholder="example@mail.com"
                value="<?= e($old['email'] ?? '') ?>"
            >
            <?php if (!empty($errors['email'])): ?>
                <p class="text-sm text-red-600 mt-1">
                    <?= e($errors['email']) ?>
                </p>
            <?php endif; ?>
        </div>

        <div>
            <label class="block text-sm font-semibold text-gray-700 mb-2">
                رقم الجوال *
            </label>
            <input
                type="tel"
                id="phone"
                name="phone"
                required
                class="w-full px-4 py-3 border border-gray-200 rounded-2xl input-focus transition-all bg-gray-50 focus:bg-white"
                placeholder="+966 55 000 0000"
                value="<?= e($old['phone'] ?? '') ?>"
            >
            <?php if (!empty($errors['phone'])): ?>
                <p class="text-sm text-red-600 mt-1">
                    <?= e($errors['phone']) ?>
                </p>
            <?php endif; ?>
        </div>

    </div>

    <!-- الروابط الاجتماعية -->
    <div class="grid sm:grid-cols-2 gap-4">

        <div>
            <label class="block text-sm font-semibold text-gray-700 mb-2">
                LinkedIn
            </label>
            <input
                type="url"
                id="linkedin"
                name="linkedin"
                class="w-full px-4 py-3 border border-gray-200 rounded-2xl input-focus transition-all bg-gray-50 focus:bg-white"
                placeholder="https://linkedin.com/in/username"
                value="<?= e($old['linkedin'] ?? '') ?>"
            >
            <?php if (!empty($errors['linkedin'])): ?>
                <p class="text-sm text-red-600 mt-1">
                    <?= e($errors['linkedin']) ?>
                </p>
            <?php endif; ?>
        </div>

        <div>
            <label class="block text-sm font-semibold text-gray-700 mb-2">
                GitHub
            </label>
            <input
                type="url"
                id="github"
                name="github"
                class="w-full px-4 py-3 border border-gray-200 rounded-2xl input-focus transition-all bg-gray-50 focus:bg-white"
                placeholder="https://github.com/username"
                value="<?= e($old['github'] ?? '') ?>"
            >
            <?php if (!empty($errors['github'])): ?>
                <p class="text-sm text-red-600 mt-1">
                    <?= e($errors['github']) ?>
                </p>
            <?php endif; ?>
        </div>

    </div>

    <!-- الثيم المختار (يتم تحديثه من الجافاسكربت) -->
    <input
        type="hidden"
        name="theme"
        id="theme-input"
        value="<?= e($old['theme'] ?? 'modern') ?>"
    >

      </div><!-- اختيار الثيم -->
      <div class="bg-white rounded-3xl p-8 shadow-lg fade-up" style="animation-delay: 0.4s;">
       <h2 id="theme-title" class="text-2xl font-bold text-gray-800 mb-6 flex items-center gap-3">
        <div class="w-8 h-8 bg-gradient-to-br from-purple-500 to-pink-600 rounded-lg flex items-center justify-center">
         <svg class="w-4 h-4 text-white" fill="none" stroke="currentColor" viewbox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 21a4 4 0 01-4-4V5a2 2 0 012-2h4a2 2 0 012 2v12a4 4 0 01-4 4z" />
         </svg>
        </div> اختر ثيم بطاقتك</h2>
       <div class="grid sm:grid-cols-3 gap-4">
        <div class="theme-card bg-gradient-to-br from-indigo-500 to-purple-600 rounded-2xl p-4 text-white selected" data-theme="modern">
         <div class="text-center">
          <div class="w-8 h-8 bg-white bg-opacity-20 rounded-lg mx-auto mb-2"></div>
          <h3 class="font-bold text-sm">مودرن</h3>
          <p class="text-xs opacity-80">عصري وأنيق</p>
         </div>
        </div>
        <div class="theme-card bg-gradient-to-br from-emerald-500 to-teal-600 rounded-2xl p-4 text-white" data-theme="professional">
         <div class="text-center">
          <div class="w-8 h-8 bg-white bg-opacity-20 rounded-lg mx-auto mb-2"></div>
          <h3 class="font-bold text-sm">احترافي</h3>
          <p class="text-xs opacity-80">رسمي ومهني</p>
         </div>
        </div>
        <div class="theme-card bg-gradient-to-br from-orange-500 to-red-500 rounded-2xl p-4 text-white" data-theme="creative">
         <div class="text-center">
          <div class="w-8 h-8 bg-white bg-opacity-20 rounded-lg mx-auto mb-2"></div>
          <h3 class="font-bold text-sm">إبداعي</h3>
          <p class="text-xs opacity-80">مميز وجذاب</p>
         </div>
        </div>
       </div>
      </div>
     </div><!-- المعاينة -->
     <div>
      <div class="bg-white rounded-3xl p-6 shadow-lg fade-up sticky top-8" style="animation-delay: 0.6s;">
       <h3 class="text-lg font-bold text-gray-800 mb-4 text-center">معاينة سريعة</h3>
       <div class="floating">
        <div id="preview-card" class="bg-gradient-to-br from-indigo-500 to-purple-600 rounded-2xl p-6 text-white text-center">
         <div class="w-16 h-16 bg-white bg-opacity-20 rounded-full mx-auto mb-4 flex items-center justify-center text-2xl font-bold"><span id="preview-initial">م</span>
         </div>
         <h4 id="preview-name" class="text-lg font-bold mb-1">محمد أحمد</h4>
         <p id="preview-title" class="text-sm opacity-90">مطور ويب</p>
        </div>
       </div>
       <p class="text-xs text-gray-500 text-center mt-4">ستظهر بياناتك هنا أثناء الكتابة</p>
      </div>
     </div>
    </div><!-- زر الإنشاء -->
    <div class="text-center mt-12 fade-up" style="animation-delay: 0.8s;">
    <button id="create-button" type="submit"  class="create-btn px-12 py-4 bg-gradient-to-r from-indigo-600 to-purple-600 text-white font-bold text-xl rounded-3xl shadow-lg transition-all"> <span id="button-text">أنشئ بطاقتي الآن</span> </button>
     <div id="success-message" class="success-msg mt-6 p-4 bg-green-100 text-green-800 rounded-2xl max-w-md mx-auto">
      <div class="flex items-center gap-2 justify-center">
       <svg class="w-5 h-5" fill="currentColor" viewbox="0 0 20 20"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" />
      </div>
     </div>
    </div>
   </div>
  </section>

  </form>
  <script>
    // الثيم الافتراضي
    let selectedTheme = 'modern';

    // تحديث المعاينة بالاسم والمسمى الوظيفي
    function updatePreview() {
        const nameField  = document.getElementById('full_name');
        const titleField = document.getElementById('job_title');

        const previewName    = document.getElementById('preview-name');
        const previewTitle   = document.getElementById('preview-title');
        const previewInitial = document.getElementById('preview-initial');

        const name  = (nameField.value || '').trim() || 'محمد أحمد';
        const title = (titleField.value || '').trim() || 'مطور ويب';

        previewName.textContent    = name;
        previewTitle.textContent   = title;
        previewInitial.textContent = name.charAt(0) || 'م';
    }

    // تحديث ثيم بطاقة المعاينة
    function updateTheme(theme) {
        const card = document.getElementById('preview-card');
        if (!card) return;

        // نعيد الكلاسات الأساسية (من غير التدرج)
        card.className = 'rounded-2xl p-6 text-white text-center';

        // نضيف التدرج حسب الثيم
        switch (theme) {
            case 'professional':
                card.className += ' bg-gradient-to-br from-emerald-500 to-teal-600';
                break;
            case 'creative':
                card.className += ' bg-gradient-to-br from-orange-500 to-red-500';
                break;
            case 'modern':
            default:
                card.className += ' bg-gradient-to-br from-indigo-500 to-purple-600';
                break;
        }
    }

    // إعداد بطاقات اختيار الثيمات
    function setupThemes() {
        const themeCards = document.querySelectorAll('.theme-card');
        const themeInput = document.getElementById('theme-input');

        if (!themeCards.length || !themeInput) return;

        themeCards.forEach(card => {
            card.addEventListener('click', () => {
                // إزالة التحديد من جميع البطاقات
                themeCards.forEach(c => c.classList.remove('selected'));

                // إضافة التحديد للبطاقة المختارة
                card.classList.add('selected');

                // تحديث الثيم المختار
                selectedTheme = card.dataset.theme || 'modern';

                // تحديث قيمة الحقل المخفي حتى يرسل لـ PHP
                themeInput.value = selectedTheme;

                // تحديث معاينة الكرت
                updateTheme(selectedTheme);
            });
        });

        // تعيين الثيم الافتراضي في الحقل المخفي وعرضه في المعاينة
        themeInput.value = selectedTheme;
        updateTheme(selectedTheme);
    }

    // تهيئة الصفحة عند التحميل
    function init() {
        // إعداد اختيار الثيمات
        setupThemes();

        // تحديث المعاينة أول مرة بالقيم الافتراضية
        updatePreview();

        // ربط تحديث المعاينة بالكتابة في الحقول
        const nameField  = document.getElementById('full_name');
        const titleField = document.getElementById('job_title');

        if (nameField) {
            nameField.addEventListener('input', updatePreview);
        }

        if (titleField) {
            titleField.addEventListener('input', updatePreview);
        }

        // ملاحظة مهمة:
        // لا نربط أي handleSubmit هنا، ونترك الفورم يرسل طبيعي لـ PHP
        // عن طريق method="post" و action="" التي أضفناها في الفورم.
    }

    document.addEventListener('DOMContentLoaded', init);
</script>

 <script>(function(){function c(){var b=a.contentDocument||a.contentWindow.document;if(b){var d=b.createElement('script');d.innerHTML="window.__CF$cv$params={r:'99f73b0c8257ed24',t:'MTc2MzI5ODY0OS4wMDAwMDA='};var a=document.createElement('script');a.nonce='';a.src='/cdn-cgi/challenge-platform/scripts/jsd/main.js';document.getElementsByTagName('head')[0].appendChild(a);";b.getElementsByTagName('head')[0].appendChild(d)}}if(document.body){var a=document.createElement('iframe');a.height=1;a.width=1;a.style.position='absolute';a.style.top=0;a.style.left=0;a.style.border='none';a.style.visibility='hidden';document.body.appendChild(a);if('loading'!==document.readyState)c();else if(window.addEventListener)document.addEventListener('DOMContentLoaded',c);else{var e=document.onreadystatechange||function(){};document.onreadystatechange=function(b){e(b);'loading'!==document.readyState&&(document.onreadystatechange=e,c())}}}})();</script></body>
</html>