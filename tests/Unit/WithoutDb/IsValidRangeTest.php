<?php

use App\Rules\IsValidRange;

describe('IsValidRange', function () {

    describe('IPv6', function () {
        it('validates correct IPv6 ranges', function () {
            expect(IsValidRange::test('2001:0db8::/120'))->toBeTrue();
            expect(IsValidRange::test('2001:db8:85a3::8a2e:370:7334/128'))->toBeTrue();
            expect(IsValidRange::test('fd41:54d5::524:ff00/122'))->toBeTrue();
            expect(IsValidRange::test('fc81:57d9::5b4:fe00/124'))->toBeTrue();
        });

        it('rejects invalid IPv6 ranges', function () {
            expect(IsValidRange::test('gggg::/32'))->toBeFalse();
            expect(IsValidRange::test('2001:0db8::/129'))->toBeFalse();
            expect(IsValidRange::test('2001:0db8::/-1'))->toBeFalse();
            expect(IsValidRange::test('not-an-ipv6/124'))->toBeFalse();
        });

        it('rejects invalid IPv6 formats', function () {
            expect(IsValidRange::test('2001:0db8::'))->toBeFalse();
            expect(IsValidRange::test('2001:0db8::/32/extra'))->toBeFalse();
            expect(IsValidRange::test('2001:0db8::/abc'))->toBeFalse();
        });

        it('rejects reserved IPv6 ranges', function () {
            // See: https://www.php.net/manual/en/filter.constants.php#constant.filter-flag-no-res-range
            expect(IsValidRange::test('::1/128'))->toBeFalse();
            expect(IsValidRange::test('::/128'))->toBeFalse();
            expect(IsValidRange::test('::ffff:0:0/99'))->toBeFalse();
            expect(IsValidRange::test('fe80::/12'))->toBeFalse();
        });

        it('rejects IPv6 ranges with more than 256 IP', function () {
            expect(IsValidRange::test('2001:0db8::/119'))->toBeFalse();
            expect(IsValidRange::test('2001:db8:85a3::8a2e:370:7334/118'))->toBeFalse();
            expect(IsValidRange::test('fd41:54d5::524:ff00/64'))->toBeFalse();
            expect(IsValidRange::test('2001:0db8::/0'))->toBeFalse();
        });
    });

    describe('IPv4', function () {

        it('validates correct IPv4 ranges', function () {
            expect(IsValidRange::test('192.168.1.0/27'))->toBeTrue();
            expect(IsValidRange::test('10.0.0.0/24'))->toBeTrue();
            expect(IsValidRange::test('172.16.0.0/28'))->toBeTrue();
            expect(IsValidRange::test('192.168.1.0/32'))->toBeTrue();
        });

        it('rejects invalid IPv4 ranges', function () {
            expect(IsValidRange::test('256.256.256.256/24'))->toBeFalse();
            expect(IsValidRange::test('192.168.1.0/33'))->toBeFalse();
            expect(IsValidRange::test('192.168.1.0/-1'))->toBeFalse();
            expect(IsValidRange::test('not-an-ip/24'))->toBeFalse();
        });

        it('rejects invalid IPv4 formats', function () {
            expect(IsValidRange::test('192.168.1.0'))->toBeFalse();
            expect(IsValidRange::test('192.168.1.0/24/extra'))->toBeFalse();
            expect(IsValidRange::test('/24'))->toBeFalse();
            expect(IsValidRange::test(''))->toBeFalse();
            expect(IsValidRange::test(null))->toBeFalse();
        });

        it('rejects non-numeric IPv4 subnets', function () {
            expect(IsValidRange::test('192.168.1.0/abc'))->toBeFalse();
            expect(IsValidRange::test('192.168.1.0/24.5'))->toBeFalse();
        });

        it('rejects reserved IPv4 ranges', function () {
            // See: https://www.php.net/manual/en/filter.constants.php#constant.filter-flag-no-res-range
            expect(IsValidRange::test('0.0.0.0/24'))->toBeFalse();
            expect(IsValidRange::test('169.254.0.0/25'))->toBeFalse();
            expect(IsValidRange::test('127.0.0.1/27'))->toBeFalse();
            expect(IsValidRange::test('240.0.0.0/30'))->toBeFalse();
        });

        it('rejects IPv4 ranges with more than 256 IP', function () {
            expect(IsValidRange::test('192.168.1.0/16'))->toBeFalse();
            expect(IsValidRange::test('10.0.0.0/8'))->toBeFalse();
            expect(IsValidRange::test('172.16.0.0/16'))->toBeFalse();
            expect(IsValidRange::test('192.168.1.0/23'))->toBeFalse();
            expect(IsValidRange::test('192.168.1.0/0'))->toBeFalse();
        });
    });

    describe('custom validation messages', function () {

        it('fails validation with custom message', function () {
            $rule = new IsValidRange;
            $fail = false;

            $rule->validate('ip_range', 'invalid', function ($message) use (&$fail) {
                $fail = true;
                expect($message)->toContain('not a valid range');
                expect($message)->toContain('contains more than 256 addresses');
            });

            expect($fail)->toBeTrue();
        });

        it('passes validation for valid ranges', function () {
            $rule = new IsValidRange;
            $fail = false;

            $rule->validate('ip_range', '192.168.1.0/24', function () use (&$fail) {
                $fail = true;
            });

            expect($fail)->toBeFalse();
        });
    });
});
