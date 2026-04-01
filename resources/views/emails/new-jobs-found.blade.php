<!DOCTYPE html>
<html lang="{{ $user->locale ?? 'en' }}">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ __('emails.title') }}</title>
    <style>
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Helvetica, Arial, sans-serif;
            line-height: 1.6;
            color: #333;
            max-width: 600px;
            margin: 0 auto;
            padding: 20px;
            background-color: #f5f5f5;
        }
        .container {
            background-color: #ffffff;
            border-radius: 8px;
            padding: 30px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
        h1 {
            color: #1a1a1a;
            font-size: 24px;
            margin-bottom: 10px;
        }
        .greeting {
            color: #666;
            margin-bottom: 25px;
        }
        .company-section {
            margin-bottom: 30px;
            padding-bottom: 20px;
            border-bottom: 1px solid #e5e5e5;
        }
        .company-section:last-child {
            border-bottom: none;
        }
        .company-name {
            color: #1a1a1a;
            font-size: 18px;
            font-weight: 600;
            margin-bottom: 15px;
        }
        .job-item {
            background-color: #f9f9f9;
            border-left: 3px solid #fbbf24;
            padding: 15px;
            margin-bottom: 12px;
            border-radius: 4px;
        }
        .job-title {
            font-size: 16px;
            font-weight: 600;
            color: #1a1a1a;
            margin-bottom: 6px;
        }
        .job-meta {
            font-size: 14px;
            color: #666;
            margin-bottom: 8px;
        }
        .job-meta span {
            margin-right: 15px;
        }
        .apply-link {
            display: inline-block;
            background-color: #fbbf24;
            color: #1a1a1a;
            padding: 8px 16px;
            text-decoration: none;
            border-radius: 4px;
            font-size: 14px;
            font-weight: 500;
            margin-top: 8px;
        }
        .apply-link:hover {
            background-color: #f59e0b;
        }
        .footer {
            margin-top: 30px;
            padding-top: 20px;
            border-top: 1px solid #e5e5e5;
            font-size: 12px;
            color: #999;
            text-align: center;
        }
        .summary {
            background-color: #fef3c7;
            padding: 15px;
            border-radius: 4px;
            margin-bottom: 25px;
            font-size: 14px;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>🎯 {{ __('emails.title') }}</h1>

        <p class="greeting">
            {{ __('emails.greeting', ['name' => $user->name]) }}
        </p>

        <div class="summary">
            {!! __('emails.summary', [
                'jobs' => '<strong>' . $totalJobs . '</strong>',
                'jobs_label' => $totalJobs === 1 ? __('emails.job_singular') : __('emails.job_plural'),
                'companies' => '<strong>' . $jobsByCompany->count() . '</strong>',
                'companies_label' => $jobsByCompany->count() === 1 ? __('emails.company_singular') : __('emails.company_plural'),
            ]) !!}
        </div>

        @foreach($jobsByCompany as $item)
            <div class="company-section">
                <div class="company-name">
                    {{ $item['company']->name }}
                </div>

                @foreach($item['jobs'] as $job)
                    <div class="job-item">
                        <div class="job-title">
                            {{ $job->title }}
                        </div>

                        <div class="job-meta">
                            @if($job->location)
                                <span>📍 {{ $job->location }}</span>
                            @endif
                            @if($job->department)
                                <span>🏢 {{ $job->department }}</span>
                            @endif
                        </div>

                        <a href="{{ $job->url }}" class="apply-link" target="_blank">
                            {{ __('emails.view_apply') }} →
                        </a>
                    </div>
                @endforeach
            </div>
        @endforeach

        @if(isset($failures) && $failures->isNotEmpty())
            <div style="background-color: #fef2f2; padding: 15px; border-radius: 4px; margin-top: 25px; border-left: 3px solid #dc2626;">
                <p style="font-weight: 600; color: #dc2626; margin-top: 0;">
                    Unable to update some companies
                </p>
                <p style="font-size: 14px; color: #666; margin-bottom: 0;">
                    We couldn't fetch the latest jobs from:
                    @foreach($failures as $failure)
                        <strong>{{ $failure['company_name'] }}</strong>{{ $loop->last ? '.' : ', ' }}
                    @endforeach
                    This is usually temporary. If it persists, the team has been notified.
                </p>
            </div>
        @endif

        <div class="footer">
            <p>
                {{ __('emails.footer') }}
            </p>
        </div>
    </div>
</body>
</html>
