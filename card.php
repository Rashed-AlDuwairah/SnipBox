<?php
declare(strict_types=1);

const CARDS_FILE = __DIR__ . '/cards.json';

// دالة تهريب النصوص للعرض
function e(string $value): string {
    return htmlspecialchars($value, ENT_QUOTES, 'UTF-8');
}

// كيان البطاقة (نفس الفكرة من create.php)
class Card {
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

    public static function fromArray(array $data): self {
        return new self(
            $data['id'],
            $data['name'],
            $data['job_title'] ?? '',
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

// مستودع البطاقات من ملف JSON
class CardRepository {
    public function __construct(private string $filePath = CARDS_FILE) {}

    /**
     * @return Card[]
     */
    public function all(): array {
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

    public function findById(string $id): ?Card {
        foreach ($this->all() as $card) {
            if ($card->id === $id) {
                return $card;
            }
        }
        return null;
    }
}

// === جلب الـ id من الرابط والتحقق ===
$id = $_GET['id'] ?? '';
$id = is_string($id) ? trim($id) : '';

if ($id === '') {
    http_response_code(400);
    $errorMessage = 'لم يتم تمرير معرف الكرت.';
    $card = null;
} else {
    $repo = new CardRepository();
    $card = $repo->findById($id);

    if (!$card) {
        http_response_code(404);
        $errorMessage = 'عذراً، الكرت المطلوب غير موجود أو ربما تم حذفه.';
    }
}

// إعداد قيم افتراضية للعرض في حال وجود بطاقة
$headerTitle    = 'MyCard';
$headerSubtitle = 'هذه بطاقتك الرقمية – يمكنك مشاركة هذا الرابط مع الآخرين.';
$copyButtonText = 'نسخ الرابط';
$shareMessage   = 'انسخ هذا الرابط وشاركه مع أصحاب العمل أو الأصدقاء.';

?>
<!doctype html>
<html lang="ar" dir="rtl">
 <head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>بطاقة العمل الرقمية</title>
  <script src="https://cdn.tailwindcss.com"></script>
  <script src="/_sdk/element_sdk.js"></script>
  <link href="https://fonts.googleapis.com/css2?family=Cairo:wght@400;600;700&amp;display=swap" rel="stylesheet">
  <style>
        html, body {
            box-sizing: border-box;
            font-family: 'Cairo', sans-serif;
            height: 100%;
            margin: 0;
            padding: 0;
        }
        
        .gradient-bg {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        }
        
        .card-animation {
            animation: slideUp 0.8s ease-out;
        }
        
        @keyframes slideUp {
            from {
                opacity: 0;
                transform: translateY(30px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }
        
        .hover-effect {
            transition: all 0.3s ease;
        }
        
        .hover-effect:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 25px rgba(0,0,0,0.2);
        }
        
        .social-hover {
            transition: all 0.3s ease;
        }
        
        .social-hover:hover {
            transform: scale(1.1);
            background-color: rgba(255,255,255,0.3);
        }
        
        .copy-notification {
            opacity: 0;
            transform: translateY(10px);
            transition: all 0.4s ease;
        }
        
        .copy-notification.show {
            opacity: 1;
            transform: translateY(0);
        }
    </style>
  <style>@view-transition { navigation: auto; }</style>
  <script src="/_sdk/data_sdk.js" type="text/javascript"></script>
 </head>
 <body class="gradient-bg flex items-center justify-center p-4" style="min-height: 100%;"><!-- البطاقة الرئيسية -->
 
 
 <?php
$name     = $card->name;
$jobTitle = $card->jobTitle ?: 'مطور ويب';
$email    = $card->email;
$phone    = $card->phone;
$linkedin = $card->linkedin;
$github   = $card->github;

// أول حرف من الاسم (يدعم العربية)
$initial = mb_substr($name, 0, 1, 'UTF-8');

// اختيار ألوان الهيدر حسب الثيم
$gradientClasses = 'from-indigo-500 to-purple-600'; // modern
switch ($card->theme) {
    case 'professional':
        $gradientClasses = 'from-emerald-500 to-teal-600';
        break;
    case 'creative':
        $gradientClasses = 'from-orange-500 to-rose-500';
        break;
    case 'modern':
    default:
        $gradientClasses = 'from-indigo-500 to-purple-600';
        break;
}
?>


 <div class="card-animation max-w-md w-full">
    <!-- بطاقة العمل -->
    <div class="bg-white rounded-3xl shadow-2xl overflow-hidden hover-effect">

        <!-- الهيدر مع الخلفية الملونة -->
        <div class="bg-gradient-to-r <?= e($gradientClasses) ?> p-8 text-white text-center relative">
            <!-- عناصر زخرفية -->
            <div class="absolute top-0 right-0 w-20 h-20 bg-white opacity-10 rounded-full -translate-y-10 translate-x-10"></div>
            <div class="absolute bottom-0 left-0 w-16 h-16 bg-white opacity-10 rounded-full translate-y-8 -translate-x-8"></div>

            <!-- الأفاتار + الاسم والمسمى -->
            <div class="relative z-10">
                <div class="w-24 h-24 bg-white bg-opacity-20 rounded-full mx-auto mb-4 flex items-center justify-center text-3xl font-bold">
                    <span id="avatar-letter"><?= e($initial) ?></span>
                </div>

                <h1 id="display-name" class="text-2xl font-bold mb-2">
                    <?= e($name) ?>
                </h1>

                <p id="display-title" class="text-lg opacity-90">
                    <?= e($jobTitle) ?>
                </p>
            </div>
        </div>

        <!-- معلومات التواصل -->
        <div class="p-8">

            <!-- البريد الإلكتروني -->
            <div class="flex items-center gap-4 mb-4 p-3 bg-gray-50 rounded-xl hover:bg-gray-100 transition-colors">
                <div class="w-10 h-10 bg-indigo-100 rounded-lg flex items-center justify-center">
                    <svg class="w-5 h-5 text-indigo-600" fill="currentColor" viewBox="0 0 20 20">
                        <path d="M2.003 5.884L10 9.882l7.997-3.998A2 2 0 0016 4H4a2 2 0 00-1.997 1.884z" />
                        <path d="M18 8.118l-8 4-8-4V14a2 2 0 002 2h12a2 2 0 002-2V8.118z" />
                    </svg>
                </div>
                <div>
                    <p class="text-sm text-gray-500">البريد الإلكتروني</p>
                    <a
                        href="mailto:<?= e($email) ?>"
                        id="display-email"
                        class="text-gray-800 font-medium hover:text-indigo-600"
                    >
                        <?= e($email) ?>
                    </a>
                </div>
            </div>

            <!-- رقم الجوال -->
            <div class="flex items-center gap-4 mb-6 p-3 bg-gray-50 rounded-xl hover:bg-gray-100 transition-colors">
                <div class="w-10 h-10 bg-green-100 rounded-lg flex items-center justify-center">
                    <svg class="w-5 h-5 text-green-600" fill="currentColor" viewBox="0 0 20 20">
                        <path d="M2 3a1 1 0 011-1h2.153a1 1 0 01.986.836l.74 4.435a1 1 0 01-.54 1.06l-1.548.773a11.037 11.037 0 006.105 6.105l.774-1.548a1 1 0 011.059-.54l4.435.74a1 1 0 01.836.986V17a1 1 0 01-1 1h-2C7.82 18 2 12.18 2 5V3z" />
                    </svg>
                </div>
                <div>
                    <p class="text-sm text-gray-500">رقم الجوال</p>
                    <a
                        href="tel:<?= e($phone) ?>"
                        id="display-phone"
                        class="text-gray-800 font-medium hover:text-green-600"
                    >
                        <?= e($phone) ?>
                    </a>
                </div>
            </div>

            <!-- الروابط الاجتماعية (LinkedIn + GitHub فقط) -->
            <?php if ($linkedin || $github): ?>
                <div class="flex justify-center gap-4 mb-6">

                    <?php if ($linkedin): ?>
                        <a
                            href="<?= e($linkedin) ?>"
                            id="linkedin-link"
                            target="_blank"
                            rel="noopener noreferrer"
                            class="social-hover w-12 h-12 bg-blue-100 rounded-xl flex items-center justify-center"
                        >
                            <svg class="w-6 h-6 text-blue-600" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd"
                                      d="M16.338 16.338H13.67V12.16c0-.995-.017-2.277-1.387-2.277-1.39 0-1.601 1.086-1.601 2.207v4.248H8.014v-8.59h2.559v1.174h.037c.356-.675 1.227-1.387 2.526-1.387 2.703 0 3.203 1.778 3.203 4.092v4.711zM5.005 6.575a1.548 1.548 0 11-.003-3.096 1.548 1.548 0 01.003 3.096zm-1.337 9.763H6.34v-8.59H3.667v8.59zM17.668 1H2.328C1.595 1 1 1.581 1 2.298v15.403C1 18.418 1.595 19 2.328 19h15.34c.734 0 1.332-.582 1.332-1.299V2.298C19 1.581 18.402 1 17.668 1z"
                                      clip-rule="evenodd" />
                            </svg>
                        </a>
                    <?php endif; ?>

                    <?php if ($github): ?>
                        <a
                            href="<?= e($github) ?>"
                            id="github-link"
                            target="_blank"
                            rel="noopener noreferrer"
                            class="social-hover w-12 h-12 bg-gray-100 rounded-xl flex items-center justify-center"
                        >
                            <svg class="w-6 h-6 text-gray-700" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd"
                                      d="M10 0C4.477 0 0 4.484 0 10.017c0 4.425 2.865 8.18 6.839 9.504.5.092.682-.217.682-.483 0-.237-.008-.868-.013-1.703-2.782.605-3.369-1.343-3.369-1.343-.454-1.158-1.11-1.466-1.11-1.466-.908-.62.069-.608.069-.608 1.003.07 1.531 1.032 1.531 1.032.892 1.53 2.341 1.088 2.91.832.092-.647.35-1.088.636-1.338-2.22-.253-4.555-1.113-4.555-4.951 0-1.093.39-1.988 1.029-2.688-.103-.253-.446-1.272.098-2.65 0 0 .84-.27 2.75 1.026A9.564 9.564 0 0110 4.844c.85.004 1.705.115 2.504.337 1.909-1.296 2.747-1.027 2.747-1.027.546 1.379.203 2.398.1 2.651.64.7 1.028 1.595 1.028 2.688 0 3.848-2.339 4.695-4.566 4.942.359.31.678.921.678 1.856 0 1.338-.012 2.419-.012 2.747 0 .268.18.58.688.482A10.019 10.019 0 0020 10.017C20 4.484 15.522 0 10 0z"
                                      clip-rule="evenodd" />
                            </svg>
                        </a>
                    <?php endif; ?>

                </div>
            <?php endif; ?>

            <!-- زر نسخ الرابط -->
            <button
                id="copy-btn"
                type="button"
                class="w-full bg-gradient-to-r from-indigo-500 to-purple-600 text-white py-3 px-6 rounded-xl font-semibold hover:from-indigo-600 hover:to-purple-700 transition-all duration-300 flex items-center justify-center gap-2"
            >
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                          d="M8 16H6a2 2 0 01-2-2V6a2 2 0 012-2h8a2 2 0 012 2v2m-6 12h8a2 2 0 002-2v-8a2 2 0 00-2-2h-8a2 2 0 00-2 2v8a2 2 0 002 2z" />
                </svg>
                نسخ رابط البطاقة
            </button>

            <!-- رسالة النسخ -->
            <div
                id="copy-message"
                class="copy-notification mt-4 p-3 bg-green-100 text-green-800 rounded-xl text-center text-sm"
            >
                <div class="flex items-center justify-center gap-2">
                    <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd"
                              d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z"
                              clip-rule="evenodd" />
                    </svg>
                    تم نسخ الرابط بنجاح!
                </div>
            </div>

        </div>
    </div>
</div>












 
  <script>
    // ضبط الأفاتار والروابط بناءً على القيم الموجودة أصلًا في الصفحة (من PHP)
    function initCardUI() {
        const nameEl   = document.getElementById('display-name');
        const avatarEl = document.getElementById('avatar-letter');
        const emailEl  = document.getElementById('display-email');
        const phoneEl  = document.getElementById('display-phone');

        // ضبط حرف الأفاتار من الاسم إن كان فارغ
        if (nameEl && avatarEl) {
            const nameText = (nameEl.textContent || '').trim();
            if (!avatarEl.textContent.trim() && nameText) {
                avatarEl.textContent = nameText.charAt(0);
            }
        }

        // ضبط mailto لو محتوى الإيميل موجود لكن href فارغ
        if (emailEl) {
            const emailText = (emailEl.textContent || '').trim();
            const href = emailEl.getAttribute('href') || '';
            if (!href && emailText) {
                emailEl.href = 'mailto:' + emailText;
            }
        }

        // ضبط tel لو محتوى الجوال موجود لكن href فارغ
        if (phoneEl) {
            const phoneText = (phoneEl.textContent || '').trim();
            const href = phoneEl.getAttribute('href') || '';
            if (!href && phoneText) {
                phoneEl.href = 'tel:' + phoneText;
            }
        }
    }

    // نسخ رابط الصفحة
    function copyLink() {
        const url = window.location.href;

        if (navigator.clipboard && navigator.clipboard.writeText) {
            navigator.clipboard.writeText(url)
                .then(showCopyMessage)
                .catch(() => fallbackCopy(url));
        } else {
            fallbackCopy(url);
        }
    }

    function fallbackCopy(text) {
        const textArea = document.createElement('textarea');
        textArea.value = text;
        textArea.style.position = 'fixed';
        textArea.style.opacity = '0';
        document.body.appendChild(textArea);
        textArea.focus();
        textArea.select();
        try {
            document.execCommand('copy');
        } catch (e) {}
        document.body.removeChild(textArea);
        showCopyMessage();
    }

    // رسالة "تم نسخ الرابط بنجاح"
    function showCopyMessage() {
        const message = document.getElementById('copy-message');
        if (!message) return;

        message.classList.add('show');
        setTimeout(() => {
            message.classList.remove('show');
        }, 3000);
    }

    function init() {
        initCardUI();

        const copyBtn = document.getElementById('copy-btn');
        if (copyBtn) {
            copyBtn.addEventListener('click', copyLink);
        }
    }

    document.addEventListener('DOMContentLoaded', init);
</script>

 
 
 
  </body>
</html>

