param(
    [string]$FirstPrefix = "kirpicore-a",
    [string]$SecondPrefix = "kirpicore-b",
    [int]$FirstHttpPort = 8080,
    [int]$SecondHttpPort = 8081,
    [int]$FirstDbPort = 3306,
    [int]$SecondDbPort = 3307
)

$ErrorActionPreference = "Stop"

function Test-Prefix([string]$Prefix) {
    if ($Prefix -notmatch '^[a-z0-9][a-z0-9_-]{1,31}$') {
        throw "Gecersiz prefix '$Prefix'. Yalnizca kucuk harf, rakam, tire ve alt cizgi kullanin (2-32 karakter)."
    }
}

function Test-Port([int]$Port, [string]$Name) {
    if ($Port -lt 1 -or $Port -gt 65535) {
        throw "$Name portu 1-65535 araliginda olmalidir."
    }
}

function Get-ComposeConfig([string]$Prefix, [int]$HttpPort, [int]$DbPort) {
    $keys = @(
        "KIRPI_APP_PREFIX",
        "KIRPI_NETWORK_NAME",
        "KIRPI_DB_VOLUME_NAME",
        "KIRPI_UPLOADS_VOLUME_NAME",
        "KIRPI_LOGS_VOLUME_NAME",
        "KIRPI_APP_HTTP_PORT",
        "KIRPI_DB_HOST_PORT"
    )
    $saved = @{}

    foreach ($key in $keys) {
        $saved[$key] = [Environment]::GetEnvironmentVariable($key, "Process")
    }

    try {
        $env:KIRPI_APP_PREFIX = $Prefix
        $env:KIRPI_NETWORK_NAME = "${Prefix}_network"
        $env:KIRPI_DB_VOLUME_NAME = "${Prefix}_mysql_data"
        $env:KIRPI_UPLOADS_VOLUME_NAME = "${Prefix}_uploads"
        $env:KIRPI_LOGS_VOLUME_NAME = "${Prefix}_logs"
        $env:KIRPI_APP_HTTP_PORT = [string]$HttpPort
        $env:KIRPI_DB_HOST_PORT = [string]$DbPort

        $json = & docker compose -f docker-compose.yml -f docker-compose.local.yml config --format json
        if ($LASTEXITCODE -ne 0) {
            throw "docker compose config basarisiz oldu."
        }

        return $json | ConvertFrom-Json
    }
    finally {
        foreach ($key in $keys) {
            [Environment]::SetEnvironmentVariable($key, $saved[$key], "Process")
        }
    }
}

Test-Prefix $FirstPrefix
Test-Prefix $SecondPrefix
Test-Port $FirstHttpPort "Birinci HTTP"
Test-Port $SecondHttpPort "Ikinci HTTP"
Test-Port $FirstDbPort "Birinci DB"
Test-Port $SecondDbPort "Ikinci DB"

if ($FirstPrefix -eq $SecondPrefix) {
    throw "Prefix degerleri farkli olmalidir."
}

if ($FirstHttpPort -eq $SecondHttpPort -or $FirstDbPort -eq $SecondDbPort) {
    throw "Iki instance ayni host portlarini kullanamaz."
}

$first = Get-ComposeConfig $FirstPrefix $FirstHttpPort $FirstDbPort
$second = Get-ComposeConfig $SecondPrefix $SecondHttpPort $SecondDbPort

$firstVolumes = @($first.volumes.PSObject.Properties.Value.name)
$secondVolumes = @($second.volumes.PSObject.Properties.Value.name)
$sharedVolumes = @($firstVolumes | Where-Object { $secondVolumes -contains $_ })

if ($first.name -eq $second.name) {
    throw "Compose proje adlari ayrismadi."
}
if ($first.networks.default.name -eq $second.networks.default.name) {
    throw "Network adlari ayrismadi."
}
if ($sharedVolumes.Count -gt 0) {
    throw "Volume adlari ayrismadi: $($sharedVolumes -join ', ')"
}

[pscustomobject]@{
    FirstProject = $first.name
    SecondProject = $second.name
    FirstNetwork = $first.networks.default.name
    SecondNetwork = $second.networks.default.name
    FirstHttpPort = $first.services.app.ports[0].published
    SecondHttpPort = $second.services.app.ports[0].published
    FirstDbPort = $first.services.db.ports[0].published
    SecondDbPort = $second.services.db.ports[0].published
    Result = "PASS"
} | Format-List
