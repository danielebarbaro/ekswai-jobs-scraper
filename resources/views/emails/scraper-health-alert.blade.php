<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Scraper Health Alert</title>
    <style>
        body { font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Helvetica, Arial, sans-serif; line-height: 1.6; color: #333; max-width: 600px; margin: 0 auto; padding: 20px; background-color: #f5f5f5; }
        .container { background-color: #ffffff; border-radius: 8px; padding: 30px; box-shadow: 0 2px 4px rgba(0,0,0,0.1); }
        h1 { color: #dc2626; font-size: 22px; }
        .failure { background-color: #fef2f2; border-left: 3px solid #dc2626; padding: 15px; margin-bottom: 12px; border-radius: 4px; }
        .failure-provider { font-weight: 600; font-size: 16px; }
        .failure-detail { font-size: 14px; color: #666; margin-top: 6px; }
        code { background: #f3f4f6; padding: 2px 6px; border-radius: 3px; font-size: 13px; }
        .footer { margin-top: 30px; font-size: 12px; color: #999; text-align: center; }
    </style>
</head>
<body>
    <div class="container">
        <h1>Scraper Health Check Failed</h1>
        <p>The following provider{{ $failures->count() === 1 ? '' : 's' }} failed the DOM health check at {{ now()->format('Y-m-d H:i:s') }}:</p>
        @foreach($failures as $failure)
            <div class="failure">
                <div class="failure-provider">{{ ucfirst($failure['provider']) }}</div>
                <div class="failure-detail">URL: {{ $failure['url'] }}</div>
                <div class="failure-detail">Expected selector: <code>{{ $failure['selector'] }}</code></div>
                <div class="failure-detail">Error: {{ $failure['error'] }}</div>
            </div>
        @endforeach
        <p>Check the scraper config in the admin panel and update the selectors if the provider has changed their HTML structure.</p>
        <div class="footer"><p>EksWai Position Scraper &mdash; Admin Alert</p></div>
    </div>
</body>
</html>
