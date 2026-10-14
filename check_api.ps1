# READ-ONLY LOCAL API CHECK — native XAMPP validation after fresh SQL import.
param(
    [string]$BaseUrl = 'http://localhost/awm-cinema/api/index.php',
    [string]$DemoPassword = 'AWMCinema123!'
)
$ErrorActionPreference = 'Stop'
function Invoke-AwmApi {
    param([string]$Route, [string]$Method = 'GET', $Body = $null, [string]$Token = '')
    $awmHeaders = @{}
    if ($Token) { $awmHeaders['Authorization'] = 'Bearer ' + $Token }
    $awmArgs = @{
        Uri = $BaseUrl + '?route=' + [Uri]::EscapeDataString($Route)
        Method = $Method
        Headers = $awmHeaders
        ContentType = 'application/json'
        TimeoutSec = 15
    }
    if ($null -ne $Body) { $awmArgs['Body'] = $Body | ConvertTo-Json -Compress }
    $awmResponse = Invoke-RestMethod @awmArgs
    if (-not $awmResponse.success) { throw 'API returned an unsuccessful response.' }
    return $awmResponse.data
}
$awmHealth = Invoke-AwmApi -Route 'health'
if ($awmHealth.database -ne 'connected') { throw 'Database check failed.' }
Write-Host 'PASS health + DB connection'
foreach ($awmAccount in @(
    @{ Email='afif@awm.test'; Role='customer' },
    @{ Email='aiman@awm.test'; Role='admin' },
    @{ Email='fairuz@awm.test'; Role='staff' }
)) {
    $awmLogin = Invoke-AwmApi -Route 'auth/login' -Method 'POST' -Body @{email=$awmAccount.Email; password=$DemoPassword}
    if ($awmLogin.user.role -ne $awmAccount.Role) { throw 'Unexpected account role.' }
    $awmMe = Invoke-AwmApi -Route 'auth/me' -Token $awmLogin.access_token
    if ($awmMe.role -ne $awmAccount.Role) { throw 'JWT/role check failed.' }
    Write-Host ('PASS login + JWT role: ' + $awmAccount.Role)
    if ($awmAccount.Role -eq 'admin') {
        $awmUsers = Invoke-AwmApi -Route 'users' -Token $awmLogin.access_token
        if ($awmUsers.pagination.total -lt 5) { throw 'Too few seeded users.' }
        Write-Host 'PASS required /users read'
    }
    if ($awmAccount.Role -eq 'staff') {
        $awmHalls = Invoke-AwmApi -Route 'operations/halls' -Token $awmLogin.access_token
        if ($awmHalls.items.Count -lt 5) { throw 'Too few halls.' }
        Write-Host 'PASS staff hall activity'
    }
    if ($awmAccount.Role -eq 'customer') {
        $awmBookings = Invoke-AwmApi -Route 'bookings' -Token $awmLogin.access_token
        Write-Host 'PASS required /bookings read'
    }
}
$awmEvents = Invoke-AwmApi -Route 'events'
$awmVenues = Invoke-AwmApi -Route 'venues'
if ($awmEvents.pagination.total -lt 5 -or $awmVenues.pagination.total -lt 5) { throw 'Too few movie/hall seed records.' }
$awmSeatMap = Invoke-AwmApi -Route ('events/' + $awmEvents.items[0].id + '/seats')
if ($awmSeatMap.seats.Count -lt 5) { throw 'Seat layout missing.' }
Write-Host 'PASS required /events + /venues + seat-map reads'
Write-Host 'Read-only checks complete. Run Postman and browser workflows for mutations, error cases, QR and concurrent seat bookings.'
