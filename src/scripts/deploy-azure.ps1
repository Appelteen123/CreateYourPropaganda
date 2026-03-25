param(
    [Parameter(Mandatory = $false)]
    [string]$ResourceGroup = "rg-fotoforum",

    [Parameter(Mandatory = $false)]
    [string]$Location = "westeurope",

    [Parameter(Mandatory = $false)]
    [string]$AppServicePlan = "asp-fotoforum-linux",

    [Parameter(Mandatory = $false)]
    [string]$WebAppName = "fotoforum-app-12345",

    [Parameter(Mandatory = $false)]
    [string]$Runtime = "PHP|8.2",

    [Parameter(Mandatory = $false)]
    [string]$DbServerName = "fotoforum-mysql-12345",

    [Parameter(Mandatory = $false)]
    [string]$DbName = "fotoforum",

    [Parameter(Mandatory = $false)]
    [string]$DbAdminUser = "fotoforumadmin",

    [Parameter(Mandatory = $true)]
    [string]$DbAdminPassword
)

$ErrorActionPreference = "Stop"

if (-not (Get-Command az -ErrorAction SilentlyContinue)) {
    throw "Azure CLI (az) is niet gevonden. Installeer eerst Azure CLI."
}

Write-Host "Inloggen op Azure..."
az account show 1>$null 2>$null
if ($LASTEXITCODE -ne 0) {
    az login
}

Write-Host "Resource group aanmaken/controleren..."
az group create --name $ResourceGroup --location $Location -o none

Write-Host "App Service Plan aanmaken/controleren..."
az appservice plan create --name $AppServicePlan --resource-group $ResourceGroup --is-linux --sku B1 -o none

Write-Host "Web App aanmaken/controleren..."
az webapp create --resource-group $ResourceGroup --plan $AppServicePlan --name $WebAppName --runtime $Runtime -o none

Write-Host "MySQL Flexible Server aanmaken/controleren..."
az mysql flexible-server create `
    --resource-group $ResourceGroup `
    --name $DbServerName `
    --location $Location `
    --admin-user $DbAdminUser `
    --admin-password $DbAdminPassword `
    --sku-name Standard_B1ms `
    --version 8.0 `
    --storage-size 32 `
    --yes -o none

Write-Host "Database aanmaken/controleren..."
az mysql flexible-server db create --resource-group $ResourceGroup --server-name $DbServerName --database-name $DbName -o none

Write-Host "Firewall-rule voor Azure services instellen..."
az mysql flexible-server firewall-rule create `
    --resource-group $ResourceGroup `
    --name $DbServerName `
    --rule-name AllowAzureServices `
    --start-ip-address 0.0.0.0 `
    --end-ip-address 0.0.0.0 -o none

$dbHost = az mysql flexible-server show --resource-group $ResourceGroup --name $DbServerName --query fullyQualifiedDomainName -o tsv

Write-Host "App settings voor database zetten..."
az webapp config appsettings set --resource-group $ResourceGroup --name $WebAppName --settings `
    DB_HOST=$dbHost `
    DB_PORT=3306 `
    DB_NAME=$DbName `
    DB_USER=$DbAdminUser `
    DB_PASSWORD=$DbAdminPassword -o none

Write-Host "Code inzippen en deployen..."
$zipPath = Join-Path $PSScriptRoot "..\site.zip"
$zipPath = (Resolve-Path $zipPath).Path

if (Test-Path $zipPath) {
    Remove-Item $zipPath -Force
}

$projectRoot = (Resolve-Path (Join-Path $PSScriptRoot "..")).Path
Push-Location $projectRoot
Compress-Archive -Path * -DestinationPath $zipPath -Force
Pop-Location

az webapp deploy --resource-group $ResourceGroup --name $WebAppName --src-path $zipPath --type zip -o none

Write-Host "Klaar. Website URL: https://$WebAppName.azurewebsites.net"
Write-Host "DB Host: $dbHost"
Write-Host "DB Name: $DbName"
Write-Host "DB User: $DbAdminUser"