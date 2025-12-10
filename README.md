📸 **Sosyal Medya API**

Bu proje, modern bir sosyal medya uygulamasının backend mimarisini Laravel 11, Sanctum ile yetkilendirme, Eloquent ORM, Database Notifications ve Swagger UI desteği ile baştan sona oluşturur. Ayrıca tüm kritik aksiyonlar Policy, Authorization, Validation ve Soft Security katmanları ile korunmaktadır.

✨ **Temel Özellikler**

Bu API, basit CRUD işlemlerinin ötesine geçerek bir sosyal medya uygulamasının gerektirdiği gelişmiş özellikleri sunar:

**Token Tabanlı Yetkilendirme:** Güvenli oturum yönetimi için Laravel Sanctum (SPA ve API Tokenları).

**Kapsamlı Kullanıcı Etkileşimleri:** Takip etme/takipten çıkma, kullanıcı engelleme/engeli kaldırma.

**İçerik Yönetimi:** Gönderi (Post) oluşturma (çoklu medya desteğiyle), düzenleme, silme ve arşivleme/arşivden çıkarma işlevleri.

**Akıllı Akış Algoritması:** Kullanıcıya özel feed; sadece takip edilenlerin ve kişinin kendi postlarını içerir, engellenen kişilerin içerikleri otomatik filtrelenir.

**Hikaye (Story) Özelliği:** 24 saat süreli medya tabanlı hikayeler oluşturma ve feed üzerinden görüntüleme. StoryPolicy ile sıkı erişim kontrolü.

**Zengin Post Etkileşimleri:** Beğenme/beğeniyi kaldırma, yorum yapma ve post kaydetme (bookmark).

**Gerçek Zamanlı Bildirimler:** Yeni takipçi, beğeni ve yorumlar için veritabanı tabanlı bildirim sistemi.

**Admin/Geliştirici Odaklı:** Spatie Activitylog ile detaylı sistem ve kullanıcı aksiyon loglaması.

🚀 **Teknolojiler**

 -Backend: PHP, Laravel Framework

 -Veritabanı: MySQL

 -Yetkilendirme: Laravel Sanctum

 -API Dökümantasyonu: Swagger

 -Loglama: Spatie Activitylog

🔑 **Endpoint'ler**

Aşağıda sistemin ana endpoint grupları listelenmiştir.
Detaylar Swagger üzerinden görülebilir.

🔐 **Yetkilendirme & Kullanıcı**

Metot,   Uç Nokta,              Açıklama

POST,   /register,          Yeni kullanıcı kaydı. (Token döndürür)

POST,   /login,             Kullanıcı girişi. (Token döndürür)

POST,   /logout,            Mevcut oturumdan çıkış. (Sanctum gereklidir)

GET,    /me,                Giriş yapan kullanıcının profil bilgilerini getirir.

PUT,    /me,                Profil bilgilerini (ad, kullanıcı adı, bio,profil fotoğrafını) günceller.

POST,   /me/avatar,         Profil fotoğrafı yükler. (Multipart form-data)

GET,    /users?search=,     Kullanıcıları name veya username ile arar.
                            (Engellenenler filtrelenir)
                            
GET,    /admin/logs,        Tüm sistem aktivitelerini listeler.(Yönetici yetkisi gerektirir)


📰 **Gönderiler (Posts)**

Metot,       Uç Nokta,                       Açıklama

GET,       /feed,                      Takip edilenlerin ve kişinin kendi postlarını içeren 
                                       ana sayfa akışı.
                                       
POST,      /posts,                     Yeni gönderi oluşturur. (Çoklu medya ve konum desteği)

PUT,       /posts/{post},              Mevcut gönderiyi düzenler. (Sadece sahibi)

DELETE,    /posts/{post},              Gönderiyi siler. (Sadece sahibi)

POST,      /posts/{post}/archive,      Gönderiyi ana akıştan kaldırır. (Sadece sahibi)

POST,      /posts/{post}/unarchive,    Arşivlenmiş gönderiyi geri alır. (Sadece sahibi)

GET,       /my-posts,                  Kişinin kendi aktif gönderilerini listeler.

❤️ **Etkileşimler & Sosyal Özellikler**

Metot,      Uç Nokta,                     Açıklama

POST,     /posts/{post}/like,        Gönderiyi beğenir/beğeniyi kaldırır. (Toggle)

GET,      /posts/{post}/likes,       Gönderiyi beğenen kullanıcıları listeler.

POST,     /posts/{post}/comments,    Gönderiye yorum yapar. (Post sahibine bildirim gider.)

DELETE,   /comments/{comment},       Yorumu siler. (Sahibi veya post sahibi silebilir)

POST,     /follow/{user},            Kullanıcıyı takip eder/takipten çıkar. (Takip edilen
                                     kullanıcıya bildirim gönderir.)
                                     
GET,      /users/{user}/followers,   Belirtilen kullanıcının takipçilerini listeler.

POST,     /block/{user},             Kullanıcıyı engeller/engeli kaldırır. (Toggle)

GET,      /blocked-users,            Giriş yapan kullanıcının engellediği kişileri listeler.

POST,     /posts/{post}/bookmark,    Gönderiyi kaydeder/kayıttan çıkarır. (Toggle)

GET,      /saved-posts,              Kaydedilmiş gönderileri listeler.

⏳ **Hikayeler (Stories) & Bildirimler**

Metot,    Uç Nokta,                         Açıklama

POST,    /stories,                       Yeni, 24 saat süreli hikaye (fotoğraf/video) ekler.

GET,     /stories/feed,                  Takip edilenlerin ve kişinin kendi aktif 
                                         hikayelerini gruplanmış olarak getirir.
                                         
POST,    /stories/{story}/view,          Hikayeyi görüntüler.(Görüntülenme sayısını artırır.)

GET,     /notifications,                 Tüm bildirimleri getirir ve okunmamışları otomatik 
                                         olarak okundu işaretler.
                                         
GET,     /notifications/unread-count,    Okunmamış bildirim sayısını getirir.


💡 **Proje Detayları ve Gelişmiş Uygulamalar**

1. *Yetkilendirme ve Loglama (Auth & Admin)*

  -Sanctum ve Activity Log: Kullanıcı kayıt, giriş ve çıkış işlemleri, IP adresi ve kullanıcı aracısı (User Agent) bilgileriyle birlikte Spatie Activitylog kullanılarak detaylıca loglanmıştır. Yönetici uç noktası (/api/admin/logs) üzerinden bu kayıtlara erişilebilir.

  -Token Yönetimi: Giriş ve kayıt sonrası dönen token, yetkili API çağrıları için kullanılmalıdır.


2. *Sosyal İlişki Yönetimi (Follow & Block)*

  -FollowController ve BlockController üzerindeki toggle metotları, tek bir çağrı ile takip etme/bırakma ve engelleme/engeli kaldırma işlemlerini gerçekleştirerek kod tekrarını azaltır ve istemci tarafındaki mantığı basitleştirir.

  -Soft Security (Engelleme Entegrasyonu): Kullanıcı arama (/users) ve ana akış (/feed) endpoint'leri, kullanıcının engellediği kişilerin içeriklerini ve profillerini otomatik olarak sonuçlardan hariç tutar.

3. *Politikalar (Policies) ile Yetkilendirme*

Proje, hassas işlemlerin yetkilendirilmesi için Laravel'in Policy yapısını kullanmaktadır:

  -PostPolicy: Bir gönderiyi sadece sahibinin düzenleyebilmesini/silebilmesini sağlar. Ayrıca post arşivleme/arşivden çıkarma işlemleri de bu politika üzerinden yetkilendirilir.

  -CommentPolicy: Yorum silme yetkisi, yorumun sahibine veya yorumun yapıldığı postun sahibine verilerek esneklik sağlanmıştır.

  -StoryPolicy: Hikayeyi sadece sahibi silebilir ve görüntüleyenleri görebilir.

4. *Medya ve Post İşlemleri*
   
  -Çoklu Medya Yükleme: PostController@store metodu, tek bir gönderi altında birden fazla fotoğraf veya video (max 10 adet) yüklenmesini destekler. Dosyalar, uygun depolama diskine kaydedilir.

  -Arşivleme (Archiving): archived_at alanı üzerinden postların "soft-delete" mantığına benzer şekilde akıştan kaldırılıp, kullanıcının profilinde kalması sağlanmıştır.
