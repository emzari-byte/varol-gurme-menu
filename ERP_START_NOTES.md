# Varol Veranda ERP Baslangic Notlari

Bu dosya yeni yazismada ERP gelistirmesine temiz baslamak icin hazirlandi.

## Proje Durumu

Mevcut sistem OpenCart 3.0.3.8 tabanli restoran QR menu, garson, mutfak, kasa ve raporlama altyapisina donustu.

Ana local yol:

```text
C:\xampp\htdocs\menu
```

Canli yol:

```text
/home/varolver/public_html/menu
```

GitHub:

```text
https://github.com/emzari-byte/varol-gurme-menu
```

Canli site:

```text
https://varolveranda.com/menu
```

## ERP Karari

ERP masaustu uygulama olarak degil, web tabanli ayri bir restoran ERP arayuzu olarak gelistirilecek.

Hedef yol:

```text
https://varolveranda.com/menu/erp
```

OpenCart admin paneli teknik/yonetim arka plani olarak kalacak. Restoran sahibi ve operasyon kullanicilari icin daha modern, sade ve restoran odakli ERP paneli olusturulacak.

Onerilen bolumler:

- `/menu/` musteri QR menu
- `/menu/admin` mevcut teknik yonetim paneli
- `/menu/erp` restoran ERP paneli
- Garson paneli mevcut route ile devam
- Mutfak paneli mevcut route ile devam
- Kasa paneli mevcut route ile devam

## Neden Web ERP?

- Kurulum gerektirmez.
- Her cihazdan acilir.
- Guncelleme tek merkezden yapilir.
- Subeli yapiya daha kolay evrilir.
- Mevcut garson, mutfak, kasa, odeme, masa ve rapor verileri zaten web tarafinda.
- Musteriye satilabilir urun hissi masaustu kurulumdan daha guclu olur.

Masaustu uygulama yalnizca ileride internet kesintisinde offline calisma, lokal yazici/kasa cekmecesi/terazi gibi donanimlara dogrudan erisim gerekirse dusunulecek.

## ERP V1 Hedefi

Ilk ERP modulu:

```text
Gün Sonu + Kasa + Satış Özeti
```

Bu modulle baslamak mantikli cunku mevcut sistemde siparis, masa, odeme, iptal ve kasa verileri zaten tutuluyor.

ERP V1 ekraninda olmasi gerekenler:

- Bugunku ciro
- Nakit toplam
- Kart toplam
- QR/online toplam
- Acik masa sayisi
- Kapanan masa sayisi
- Odeme bekleyen masa sayisi
- Tamamlanan siparis sayisi
- En cok satan urunler
- Garson bazli satis
- Masa bazli satis
- Urun iptal sebepleri
- Gun sonu kapatma
- Kasa acilis tutari
- Kasa kapanis tutari
- Kasa farki
- Gun sonu notu

## ERP V2 Hedefi

V1 stabil olduktan sonra eklenecek ana moduller:

- Stok
- Recete
- Urun maliyeti
- Fire
- Depo hareketleri
- Satin alma
- Tedarikci
- Minimum stok uyarilari
- Personel/vardiya
- Detayli raporlar

## Mevcut Veri Kaynaklari

Restoran tablolarinin bir kismi:

```text
menu_restaurant_table
menu_restaurant_table_status
menu_restaurant_order
menu_restaurant_order_product
menu_restaurant_payment
menu_restaurant_payment_item
menu_restaurant_call
menu_restaurant_order_product_cancel
menu_restaurant_cashier_log
menu_restaurant_review
menu_restaurant_review_invite
```

OpenCart urun/kategori tablolari hala menu urunleri icin kullaniliyor:

```text
menu_product
menu_product_description
menu_product_to_category
menu_category
menu_category_description
```

## Ilk Teknik Yaklasim

ERP icin yeni route dusunulecek:

```text
admin/controller/extension/module/restaurant_erp.php
admin/model/extension/module/restaurant_erp.php
admin/view/template/extension/module/restaurant_erp.twig
admin/language/tr-tr/extension/module/restaurant_erp.php
admin/language/en-gb/extension/module/restaurant_erp.php
```

Fakat kullanici deneyimi `/menu/erp` gibi tam ekran, admin chrome'u olmayan bir panel hissi vermeli.

Alternatif:

```text
admin/controller/extension/module/restaurant_erp.php
```

icinde yetkili kullanici kontrolu yapilip tam ekran template render edilir.

## Yetki Mantigi

Admin kullanici her seyi gorebilir.

ERP kullanicilari rol bazli ayrilacak:

- Admin / Isletme sahibi
- Kasa
- Depo
- Sef / Mutfak sorumlusu
- Rapor kullanicisi

Ilk versiyonda admin ve kasa kullanicisi yeterli olabilir.

## Ilk Yapilacak Is Listesi

1. `/menu/erp` giris route kararini netlestir.
2. ERP ana dashboard tasarimini olustur.
3. Gun sonu icin gerekli tabloyu kur.
4. Bugunku odeme ve satis ozetlerini modelden cek.
5. Kasa acilis/kapanis akisini ekle.
6. Gun sonu kapatma logunu tut.
7. Urun iptal raporu icin mevcut `restaurant_order_product_cancel` tablosunu kullan.
8. Garson bazli ve masa bazli satis raporlarini ekle.

## Kritik Kurallar

- Mevcut musteri QR menu akisi bozulmayacak.
- Garson, mutfak ve kasa panelindeki calisan akisa dokunurken cok dikkatli olunacak.
- ERP raporlari gecmis siparisleri silmeyecek, sadece okuyacak.
- Odeme alinmis siparisler ikinci kez tahsil edilemeyecek.
- Kasa gun sonu kapatma islemi loglanacak.
- OpenCart markasi veya OpenCart hissi ERP arayuzunde gorunmeyecek.

## Yeni Yazismaya Baslarken

Yeni yazismada su cumleyle baslanabilir:

```text
Varol Veranda ERP gelistirmesine basliyoruz. C:\xampp\htdocs\menu\ERP_START_NOTES.md dosyasini oku ve ERP V1: Gun Sonu + Kasa + Satis Ozeti moduluyle devam et.
```

