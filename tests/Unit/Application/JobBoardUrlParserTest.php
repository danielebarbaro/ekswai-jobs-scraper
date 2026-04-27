<?php

declare(strict_types=1);

use App\Application\Services\JobBoardUrlParser;
use App\Domain\Company\JobBoardProvider;

beforeEach(function (): void {
    $this->parser = new JobBoardUrlParser;
});

it('parses workable URL', function (): void {
    $result = $this->parser->parse('https://apply.workable.com/laravel');
    expect($result['provider'])->toBe(JobBoardProvider::Workable);
    expect($result['slug'])->toBe('laravel');
});

it('parses lever URL', function (): void {
    $result = $this->parser->parse('https://jobs.lever.co/scaleway');
    expect($result['provider'])->toBe(JobBoardProvider::Lever);
    expect($result['slug'])->toBe('scaleway');
});

it('parses ashby URL', function (): void {
    $result = $this->parser->parse('https://jobs.ashbyhq.com/jimdo.com');
    expect($result['provider'])->toBe(JobBoardProvider::Ashby);
    expect($result['slug'])->toBe('jimdo.com');
});

it('parses greenhouse URL', function (): void {
    $result = $this->parser->parse('https://boards.greenhouse.io/discord');
    expect($result['provider'])->toBe(JobBoardProvider::Greenhouse);
    expect($result['slug'])->toBe('discord');
});

it('parses greenhouse EU URL', function (): void {
    $result = $this->parser->parse('https://job-boards.eu.greenhouse.io/scalapaysrl');
    expect($result['provider'])->toBe(JobBoardProvider::Greenhouse);
    expect($result['slug'])->toBe('scalapaysrl');
});

it('parses greenhouse job-boards URL', function (): void {
    $result = $this->parser->parse('https://job-boards.greenhouse.io/carta');
    expect($result['provider'])->toBe(JobBoardProvider::Greenhouse);
    expect($result['slug'])->toBe('carta');
});

it('parses teamtailor URL', function (): void {
    $result = $this->parser->parse('https://weroad.teamtailor.com/jobs');
    expect($result['provider'])->toBe(JobBoardProvider::Teamtailor);
    expect($result['slug'])->toBe('weroad');
});

it('parses factorial URL', function (): void {
    $result = $this->parser->parse('https://shippypro.factorialhr.com');
    expect($result['provider'])->toBe(JobBoardProvider::Factorial);
    expect($result['slug'])->toBe('shippypro');
});

it('parses URL without https prefix', function (): void {
    $result = $this->parser->parse('apply.workable.com/laravel');
    expect($result['provider'])->toBe(JobBoardProvider::Workable);
    expect($result['slug'])->toBe('laravel');
});

it('returns null for plain slug without provider context', function (): void {
    $result = $this->parser->parse('laravel');
    expect($result)->toBeNull();
});

it('returns null for empty string', function (): void {
    $result = $this->parser->parse('');
    expect($result)->toBeNull();
});

it('strips trailing slashes and paths from slug', function (): void {
    $result = $this->parser->parse('https://jobs.lever.co/scaleway/some-job-id');
    expect($result['provider'])->toBe(JobBoardProvider::Lever);
    expect($result['slug'])->toBe('scaleway');
});

it('parses personio URL', function (): void {
    $result = $this->parser->parse('https://koro-handels-gmbh.jobs.personio.de');
    expect($result['provider'])->toBe(JobBoardProvider::Personio);
    expect($result['slug'])->toBe('koro-handels-gmbh');
});

it('parses personio URL with language query param', function (): void {
    $result = $this->parser->parse('https://koro-handels-gmbh.jobs.personio.de/?language=en');
    expect($result['provider'])->toBe(JobBoardProvider::Personio);
    expect($result['slug'])->toBe('koro-handels-gmbh');
});

it('returns null for personio root host without slug subdomain', function (): void {
    $result = $this->parser->parse('https://jobs.personio.de');
    expect($result)->toBeNull();
});
