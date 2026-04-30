# Varol Gurme Akinsoft Bridge Kurulumu

Bridge, Akinsoft kurulu bilgisayarda arka planda calisir. Port acmaz. Canli siteye HTTPS ile gider, bekleyen siparisleri ceker ve lokal Firebird veritabanina yazar.

## Demo PC Kurulumu

1. `bridge\akinsoft_bridge_config.php` dosyasini kontrol edin.
2. `bridge\run_bridge_agent.bat` ile once elle test edin.
3. Her sey basariliysa `bridge\install_bridge_task.bat` dosyasini sag tiklayip **Yonetici olarak calistir** secin.
4. Durumu kontrol etmek icin `bridge\status_bridge_task.bat` calistirin.

## Gercek Akinsoft PC Kurulumu

1. Su klasoru olusturun:

```text
C:\VarolBridge
```

2. `bridge` klasorundeki dosyalari bu klasore kopyalayin.
3. `C:\VarolBridge\akinsoft_bridge_config.php` icindeki ayarlari gercek PC'ye gore duzenleyin:

```php
'site_base_url' => 'https://varolveranda.com/menu/',
'bridge_token' => 'admin paneldeki token ile ayni olacak',
'firebird' => array(
    'host' => '127.0.0.1',
    'port' => '3050',
    'path' => 'C:\\AKINSOFT\\Wolvox9\\Database_FB\\004\\2026\\WOLVOX.FDB',
    'charset' => 'WIN1254',
    'user' => 'SYSDBA',
    'pass' => 'masterkey'
)
```

4. PHP CLI yoksa demo PC'deki calisan `C:\xampp\php` klasorunu gercek PC'de `C:\VarolBridge\php` olarak kopyalayabilirsiniz. Kopyadan sonra `C:\VarolBridge\php\php.ini` icinde su satiri duzeltin:

```ini
extension_dir="ext"
```

Gerekli eklentiler:

```text
curl
pdo_firebird
```

5. `install_bridge_task.bat` dosyasini sag tiklayip **Yonetici olarak calistir** secin.

## Masa ve Fiyat Senkronu

Canli sunucuda Firebird eklentisi gerekmedigi icin masa/fiyat senkronu da Bridge PC'den calistirilir:

```text
sync_bridge_tables.bat
sync_bridge_prices.bat
sync_bridge_all.bat
```

- `sync_bridge_tables.bat`: Akinsoft `MASA` kayitlarini canli QR menudeki masalara aktarir.
- `sync_bridge_prices.bat`: Akinsoft satis fiyatlarini OpenCart urun fiyatlarina aktarir.
- `sync_bridge_all.bat`: Ikisini arka arkaya calistirir.

Bu dosyalar sadece gerektiginde calistirilir. Siparis aktarimi icin kurulan Windows gorevi ayridir ve arka planda calismaya devam eder.

## Calisma Mantigi

- Modem portu acilmaz.
- Firebird internete acilmaz.
- cPanel'de `pdo_firebird` gerekmez.
- Windows gorevi `SYSTEM` kullanicisi ile bilgisayar acilinca otomatik baslar.
- Log dosyasi:

```text
bridge_agent.log
```

## Kaldirma

`uninstall_bridge_task.bat` dosyasini Yonetici olarak calistirin.
