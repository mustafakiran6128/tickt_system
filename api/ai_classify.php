<?php
// C:\laragon\www\destek_as\api\ai_classify.php
header('Content-Type: application/json; charset=utf-8');

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/../config/db.php';

$title = trim($_GET['text'] ?? $_GET['title'] ?? '');
$subject = mb_convert_case($title, MB_CASE_TITLE, "UTF-8");

if (empty($title)) {
    echo json_encode(['status' => 'error', 'message' => 'Title is required']);
    exit;
}

$title_lower = mb_strtolower($title, 'UTF-8');

// 1. Classify Category and Subcategory name
$category_name = 'Yazılım Geliştirme';
$subcategory_name = 'Giriş problemi';

// Yazılım Geliştirme keywords
$software_keywords = ['giriş', 'login', 'şifre', 'şifremi unuttum', 'hata', 'kod', 'ekran', 'veri', 'kayıp', 'rap', 'entegrasyon', 'yeni özellik', 'buton', 'modül', 'erisim', 'yetki', 'yavaş', 'donma', 'kasma', 'veritabani', 'db', 'api', 'hata kodu', 'bug'];
// Donanım Arızası keywords
$hardware_keywords = ['bilgisayar', 'yazıcı', 'printer', 'sunucu', 'server', 'barkod', 'okuyucu', 'tarayıcı', 'ağ', 'switch', 'router', 'modem', 'kablo', 'ekran kartı', 'ram', 'disk', 'ssd', 'fiziksel', 'arıza', 'bozuldu', 'çalışmıyor', 'kırıldı', 'kasa', 'monitör'];
// Genel Destek keywords
$general_keywords = ['lisans', 'aktivasyon', 'fatura', 'ödeme', 'ücret', 'fiyat', 'yardım', 'nasıl', 'kullanım', 'kılavuz', 'belge', 'doküman', 'destek', 'soru', 'paket', 'sözleşme'];
// Altyapı / Saha / Belediye Hizmetleri keywords
$utility_keywords = ['su', 'elektrik', 'doğalgaz', 'kesinti', 'kesilmesi', 'kanalizasyon', 'altyapı', 'belediye', 'yol', 'çöp', 'temizlik', 'sokak', 'mahalle', 'park', 'lamba', 'aydınlatma'];

$sw_hits = 0;
$hw_hits = 0;
$gn_hits = 0;
$ut_hits = 0;

foreach ($software_keywords as $kw) {
    if (mb_strpos($title_lower, $kw) !== false) $sw_hits++;
}
foreach ($hardware_keywords as $kw) {
    if (mb_strpos($title_lower, $kw) !== false) $hw_hits++;
}
foreach ($general_keywords as $kw) {
    if (mb_strpos($title_lower, $kw) !== false) $gn_hits++;
}
foreach ($utility_keywords as $kw) {
    if (mb_strpos($title_lower, $kw) !== false) $ut_hits++;
}

// Boost General category weight if utility keywords matched
if ($ut_hits > 0) {
    $gn_hits += ($ut_hits * 5);
}

// Select the category with the most hits
if ($hw_hits > $sw_hits && $hw_hits > $gn_hits) {
    $category_name = 'Donanım Arızası';
    $subcategory_name = 'Bilgisayar';
    
    if (mb_strpos($title_lower, 'yazıcı') !== false || mb_strpos($title_lower, 'printer') !== false || mb_strpos($title_lower, 'kartuş') !== false) {
        $subcategory_name = 'Yazıcı';
    } elseif (mb_strpos($title_lower, 'sunucu') !== false || mb_strpos($title_lower, 'server') !== false) {
        $subcategory_name = 'Sunucu';
    } elseif (mb_strpos($title_lower, 'barkod') !== false || mb_strpos($title_lower, 'okuyucu') !== false) {
        $subcategory_name = 'Barkod okuyucu';
    } elseif (mb_strpos($title_lower, 'ağ') !== false || mb_strpos($title_lower, 'network') !== false || mb_strpos($title_lower, 'switch') !== false || mb_strpos($title_lower, 'router') !== false || mb_strpos($title_lower, 'modem') !== false || mb_strpos($title_lower, 'internet') !== false || mb_strpos($title_lower, 'wifi') !== false) {
        $subcategory_name = 'Ağ cihazı';
    }
} elseif ($gn_hits > $sw_hits && $gn_hits > $hw_hits) {
    $category_name = 'Genel Destek';
    $subcategory_name = 'Lisans aktivasyonu';
    
    if (mb_strpos($title_lower, 'lisans') !== false || mb_strpos($title_lower, 'aktivasyon') !== false || mb_strpos($title_lower, 'serial') !== false || mb_strpos($title_lower, 'key') !== false) {
        $subcategory_name = 'Lisans aktivasyonu';
    } elseif (mb_strpos($title_lower, 'fatura') !== false || mb_strpos($title_lower, 'ödeme') !== false || mb_strpos($title_lower, 'borç') !== false || mb_strpos($title_lower, 'fiyat') !== false || mb_strpos($title_lower, 'ücret') !== false) {
        $subcategory_name = 'Fatura sorgulama';
    } elseif (mb_strpos($title_lower, 'yardım') !== false || mb_strpos($title_lower, 'nasıl') !== false || mb_strpos($title_lower, 'kullanım') !== false || mb_strpos($title_lower, 'kılavuz') !== false) {
        $subcategory_name = 'Kullanım yardımı';
    } elseif ($ut_hits > 0) {
        $subcategory_name = 'Saha & Altyapı Arızası';
    }
} else {
    // Default to Yazılım Geliştirme
    $category_name = 'Yazılım Geliştirme';
    $subcategory_name = 'Giriş problemi';
    
    if (mb_strpos($title_lower, 'giriş') !== false || mb_strpos($title_lower, 'login') !== false || mb_strpos($title_lower, 'şifre') !== false) {
        $subcategory_name = 'Giriş problemi';
    } elseif (mb_strpos($title_lower, 'rapor') !== false || mb_strpos($title_lower, 'raporlama') !== false || mb_strpos($title_lower, 'grafik') !== false || mb_strpos($title_lower, 'analiz') !== false) {
        $subcategory_name = 'Raporlama hatası';
    } elseif (mb_strpos($title_lower, 'kayıp') !== false || mb_strpos($title_lower, 'silindi') !== false || mb_strpos($title_lower, 'veri') !== false || mb_strpos($title_lower, 'data') !== false) {
        $subcategory_name = 'Veri kaybı';
    } elseif (mb_strpos($title_lower, 'entegrasyon') !== false || mb_strpos($title_lower, 'bağlantı') !== false || mb_strpos($title_lower, 'api') !== false) {
        $subcategory_name = 'Entegrasyon sorunu';
    } elseif (mb_strpos($title_lower, 'özellik') !== false || mb_strpos($title_lower, 'istek') !== false || mb_strpos($title_lower, 'yeni') !== false) {
        $subcategory_name = 'Yeni özellik talebi';
    }
}

// 2. Classify Priority
$priority_name = 'Normal';
if (mb_strpos($title_lower, 'kritik') !== false || mb_strpos($title_lower, 'acil') !== false || mb_strpos($title_lower, 'çöktü') !== false || mb_strpos($title_lower, 'down') !== false || mb_strpos($title_lower, 'veri kaybı') !== false || mb_strpos($title_lower, 'güvenlik') !== false) {
    $priority_name = 'Acil';
} elseif (mb_strpos($title_lower, 'yüksek') !== false || mb_strpos($title_lower, 'bozuk') !== false || mb_strpos($title_lower, 'hata') !== false || $ut_hits > 0) {
    $priority_name = 'Yüksek';
} elseif (mb_strpos($title_lower, 'nasıl') !== false || mb_strpos($title_lower, 'soru') !== false || mb_strpos($title_lower, 'yardım') !== false || mb_strpos($title_lower, 'yeni özellik') !== false) {
    $priority_name = 'Düşük';
}

// 3. Classify Ticket Type
$ticket_type_name = 'Arıza';
if (mb_strpos($title_lower, 'istek') !== false || mb_strpos($title_lower, 'yeni özellik') !== false || mb_strpos($title_lower, 'eklensin') !== false || mb_strpos($title_lower, 'yapılsın') !== false) {
    $ticket_type_name = 'İstek';
} elseif (mb_strpos($title_lower, 'nasıl') !== false || mb_strpos($title_lower, 'bilgi') !== false || mb_strpos($title_lower, 'yardım') !== false) {
    $ticket_type_name = 'Soru';
} elseif (mb_strpos($title_lower, 'güncelle') !== false || mb_strpos($title_lower, 'değiştir') !== false || mb_strpos($title_lower, 'değişiklik') !== false) {
    $ticket_type_name = 'Değişiklik Talebi';
}

// 4. Classify Product/Service
$product_service = 'Genel Hizmet';
if ($ut_hits > 0) {
    $product_service = 'Saha & Altyapı Servisleri';
} elseif (mb_strpos($title_lower, 'muhasebe') !== false || mb_strpos($title_lower, 'fatura') !== false || mb_strpos($title_lower, 'ödeme') !== false) {
    $product_service = 'Muhasebe Modülü';
} elseif (mb_strpos($title_lower, 'crm') !== false || mb_strpos($title_lower, 'müşteri') !== false) {
    $product_service = 'CRM Müşteri Paneli';
} elseif (mb_strpos($title_lower, 'erp') !== false || mb_strpos($title_lower, 'sistem') !== false) {
    $product_service = 'ERP Sistem Yönetimi';
} elseif (mb_strpos($title_lower, 'ik') !== false || mb_strpos($title_lower, 'insan kaynakları') !== false) {
    $product_service = 'İnsan Kaynakları Portalı';
}

// 5. Classify Project Name
$project_name = 'Destek A.Ş. Kurumsal';
if ($ut_hits > 0) {
    $project_name = 'Destek A.Ş. Saha Yönetimi';
} elseif (mb_strpos($title_lower, 'erp') !== false) {
    $project_name = 'Destek A.Ş. ERP';
} elseif (mb_strpos($title_lower, 'muhasebe') !== false || mb_strpos($title_lower, 'fatura') !== false) {
    $project_name = 'Destek A.Ş. Finans';
} elseif (mb_strpos($title_lower, 'crm') !== false) {
    $project_name = 'Destek A.Ş. CRM';
}

// 6. Generate Description Suggestion based on subcategory or keywords
$description_suggestion = "Karşılaştığım sorun/talep ile ilgili detaylar aşağıdaki gibidir:\n\n1. Karşılaşılan Durum: \n2. Hatanın Adımları: \n3. Beklenen Sonuç: ";

if ($subcategory_name === 'Giriş problemi') {
    $description_suggestion = "Sisteme giriş yapmaya çalıştığımda kullanıcı adı veya şifre hatası alıyorum.\n\nKullanıcı E-postası: \nAlınan Hata Mesajı: \nHatanın Gerçekleştiği Sayfa: Giriş Ekranı";
} elseif ($subcategory_name === 'Saha & Altyapı Arızası') {
    $description_suggestion = "{$subject} Bildirimi:\n\nİlgili Mahalle / Sokak: \nBina / Kapı No: \nKesinti Başlangıç Zamanı: \nAçıklama: Kesinti/arıza hakkında detaylı bilgi giriniz.";
} elseif ($subcategory_name === 'Raporlama hatası') {
    $description_suggestion = "Sistem içerisindeki raporlama modülünde veri listeleme veya grafik yükleme hatası ile karşılaşıyorum.\n\nHatalı Rapor Adı: \nSeçilen Filtreler: \nHata Detayı: Ekran yüklenirken kalıyor veya veriler eksik geliyor.";
} elseif ($subcategory_name === 'Veri kaybı') {
    $description_suggestion = "Sistemde daha önce kayıtlı olan verilerimi görüntüleyemiyorum.\n\nKayıp Veri Türü: \nKayıt ID / Zamanı: \nAçıklama: Veri tabanında veya listede kayıtlar görünmüyor.";
} elseif ($subcategory_name === 'Entegrasyon sorunu') {
    $description_suggestion = "API veya dış sistem entegrasyonunda bağlantı hatası alıyoruz.\n\nİlgili Servis: \nHata Kodu / Mesajı: \nİstek URL'si: ";
} elseif ($subcategory_name === 'Yeni özellik talebi') {
    $description_suggestion = "Sistemimize yeni bir işlevsellik veya geliştirme eklenmesini talep ediyoruz.\n\nTalep Edilen Özellik: \nİş İhtiyacı / Neden Gerekli: \nÖrnek Senaryo: ";
} elseif ($subcategory_name === 'Yazıcı') {
    $description_suggestion = "Fiziksel yazıcımızdan çıktı almaya çalıştığımızda baskı hatası alıyoruz.\n\nYazıcı Marka/Modeli: \nBağlantı Türü (USB/Ağ): \nArıza Detayı: Yazıcı çıktı vermiyor veya kağıt sıkışma uyarısı veriyor.";
} elseif ($subcategory_name === 'Sunucu') {
    $description_suggestion = "Sunucumuza veya host sunucumuza erişim sağlayamıyoruz.\n\nSunucu IP/Adresi: \nErişim Türü (SSH/RDP): \nHata Detayı: Bağlantı zaman aşımına uğruyor.";
} elseif ($subcategory_name === 'Ağ cihazı') {
    $description_suggestion = "İnternet bağlantımızda veya lokal ağda kesintiler yaşıyoruz.\n\nEtkilenen Cihaz (Modem/Switch): \nSorun Detayı: İnternet ışığı yanmıyor veya cihaz kilitlenmiş durumda.";
} elseif ($subcategory_name === 'Lisans aktivasyonu') {
    $description_suggestion = "Sistem lisans süresi veya aktivasyon anahtarı işlemleri hakkında destek talep ediyoruz.\n\nLisans Anahtarı: \nAlınan Uyarı: Lisans süresi doldu veya geçersiz anahtar.";
} elseif ($subcategory_name === 'Fatura sorgulama' || mb_strpos($title_lower, 'maddi') !== false) {
    $description_suggestion = "Ödeme onayları, fatura kopyaları veya maddi ödeme süreçleriyle ilgili işlemler:\n\nFirma Unvanı: \nFatura Numarası/Dönemi: \nTalep Detayı: Ödeme onayı veya fatura kopyasının e-posta olarak gönderilmesi.";
} elseif ($subcategory_name === 'Kullanım yardımı') {
    $description_suggestion = "Sistem içerisindeki özelliklerin kullanımı hakkında yardım talep ediyoruz.\n\nKullanılmak İstenen Alan: \nSorulan Soru: Bu özelliğin çalışma mantığı ve adımları nelerdir?";
}

// Query actual IDs from database to map names to database IDs dynamically
try {
    // Get Category ID
    $stmt = $pdo->prepare("SELECT id FROM categories WHERE name = ? LIMIT 1");
    $stmt->execute([$category_name]);
    $category_id = $stmt->fetchColumn() ?: 1;

    // Get Subcategory ID
    $stmt = $pdo->prepare("SELECT id FROM subcategories WHERE name = ? AND category_id = ? LIMIT 1");
    $stmt->execute([$subcategory_name, $category_id]);
    $subcategory_id = $stmt->fetchColumn() ?: 1;

    // Get Priority ID
    $stmt = $pdo->prepare("SELECT id FROM priorities WHERE name = ? LIMIT 1");
    $stmt->execute([$priority_name]);
    $priority_id = $stmt->fetchColumn() ?: 2; // Default to Normal (ID 2)

    // Get Ticket Type ID
    $stmt = $pdo->prepare("SELECT id FROM ticket_types WHERE name = ? LIMIT 1");
    $stmt->execute([$ticket_type_name]);
    $ticket_type_id = $stmt->fetchColumn() ?: 1; // Default to Arıza (ID 1)

    // Calculate relevant categories dynamically based on hits
    $relevant_categories = [];
    if ($sw_hits > 0) {
        $stmtC = $pdo->prepare("SELECT id FROM categories WHERE name = 'Yazılım Geliştirme' LIMIT 1");
        $stmtC->execute();
        $cid = $stmtC->fetchColumn();
        if ($cid) $relevant_categories[] = (int)$cid;
    }
    if ($hw_hits > 0) {
        $stmtC = $pdo->prepare("SELECT id FROM categories WHERE name = 'Donanım Arızası' LIMIT 1");
        $stmtC->execute();
        $cid = $stmtC->fetchColumn();
        if ($cid) $relevant_categories[] = (int)$cid;
    }
    if ($gn_hits > 0) {
        $stmtC = $pdo->prepare("SELECT id FROM categories WHERE name = 'Genel Destek' LIMIT 1");
        $stmtC->execute();
        $cid = $stmtC->fetchColumn();
        if ($cid) $relevant_categories[] = (int)$cid;
    }

    if (empty($relevant_categories)) {
        $stmtC = $pdo->query("SELECT id FROM categories WHERE status = 'active'");
        $relevant_categories = $stmtC->fetchAll(PDO::FETCH_COLUMN);
        $relevant_categories = array_map('intval', $relevant_categories);
    }

    // Output JSON
    echo json_encode([
        'status' => 'success',
        'data' => [
            'category_id' => $category_id,
            'subcategory_id' => $subcategory_id,
            'priority_id' => $priority_id,
            'ticket_type_id' => $ticket_type_id,
            'product_service' => $product_service,
            'project_name' => $project_name,
            'description_suggestion' => $description_suggestion,
            'relevant_categories' => $relevant_categories,
            'ai_summary' => "Kategori: {$category_name} > {$subcategory_name} | Öncelik: {$priority_name} | Tür: {$ticket_type_name}"
        ]
    ]);
} catch (\Exception $e) {
    echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
}
?>
