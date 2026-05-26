<?php

namespace Tests\Unit;

use App\Support\WordpressDomainHelper;
use PHPUnit\Framework\TestCase;

class WordpressDomainHelperTest extends TestCase
{
    public function test_normalize_hostname_strips_scheme(): void
    {
        $this->assertSame('example.com', WordpressDomainHelper::normalizeHostname('https://Example.com/'));
    }

    public function test_apex_from_www_hostname(): void
    {
        $this->assertSame('example.com', WordpressDomainHelper::apexFromHostname('www.example.com'));
    }

    public function test_is_subdomain_of_base(): void
    {
        $this->assertTrue(WordpressDomainHelper::isSubdomainOfBase('shop.claudsoft.com', 'claudsoft.com'));
        $this->assertFalse(WordpressDomainHelper::isSubdomainOfBase('example.com', 'claudsoft.com'));
    }

    public function test_dns_record_for_apex(): void
    {
        $dns = WordpressDomainHelper::dnsRecordForPrimaryHostname('example.com', 'example.com');
        $this->assertSame('@', $dns['record_name']);
        $this->assertSame('example.com', $dns['fqdn']);
    }

    public function test_filebrowser_hostname(): void
    {
        $this->assertSame('files.example.com', WordpressDomainHelper::filebrowserHostname('example.com'));
    }
}
