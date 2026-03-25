# Azure setup voor FotoForum (zonder database-engine)

Deze variant deployt alleen de app en slaat alles lokaal op in de App Service:

- Data: JSON-bestanden (`users`, `posts`, `votes`)
- Afbeeldingen: lokale uploadmap

## 1) Maak de Web App in Azure Portal

1. Maak een **Resource Group** (bijv. `rg-fotoforum`).
2. Maak een **App Service Plan** (Linux, bijv. B1).
3. Maak een **Web App**:
   - Publish: Code
   - Runtime stack: PHP 8.2
   - Operating System: Linux
   - Region: zelfde regio als je plan

## 2) Deploy je code (zonder script)

Gebruik in Azure Portal **Deployment Center**:

1. Open je Web App.
2. Ga naar **Deployment Center**.
3. Kies **Source: GitHub**.
4. Koppel repository + branch (`main`).
5. Save/Complete zodat deployment wordt gestart.

## 3) Zet App Settings voor lokale opslag

Ga naar **Web App > Settings > Environment variables** en voeg toe:

- `DATA_DIR_ABS` = `/home/site/data/fotoforum`
- `UPLOAD_DIR_ABS` = `/home/site/wwwroot/uploads`
- `UPLOAD_URL_PREFIX` = `uploads`

Klik op **Apply** en daarna **Restart** de Web App.

## 4) Eerste run en rechten

1. Open `https://<jouw-appnaam>.azurewebsites.net/register.php`
2. Maak 1 testaccount aan.
3. Upload 1 testfoto via `/upload.php`.

De app maakt automatisch:

- Datamap op `/home/site/data/fotoforum`
- JSON-bestanden voor users/posts/votes
- Uploadmap op `/home/site/wwwroot/uploads` (als die nog niet bestaat)

## 5) Belangrijke beperking

Deze opslag is lokaal op 1 App Service instance. Dit is prima voor kleine projecten, maar niet ideaal voor zware schaal of meerdere instances.

## 6) Optioneel: later upgraden

Als je later wilt opschalen, kun je overstappen naar:

- Azure Database for MySQL (managed)
- Azure Blob Storage voor uploads
