<?php

declare(strict_types=1);

namespace PackageVersions;

use Composer\InstalledVersions;
use OutOfBoundsException;

class_exists(InstalledVersions::class);

/**
 * This class is generated by composer/package-versions-deprecated, specifically by
 * @see \PackageVersions\Installer
 *
 * This file is overwritten at every run of `composer install` or `composer update`.
 *
 * @deprecated in favor of the Composer\InstalledVersions class provided by Composer 2. Require composer-runtime-api:^2 to ensure it is present.
 */
final class Versions
{
    /**
     * @deprecated please use {@see self::rootPackageName()} instead.
     *             This constant will be removed in version 2.0.0.
     */
    const ROOT_PACKAGE_NAME = '__root__';

    /**
     * Array of all available composer packages.
     * Dont read this array from your calling code, but use the \PackageVersions\Versions::getVersion() method instead.
     *
     * @var array<string, string>
     * @internal
     */
    const VERSIONS          = array (
  'api-platform/core' => 'v2.6.6@25c71b216473844cdbe5bf284ccbeeb26acd76e7',
  'behat/transliterator' => 'v1.3.0@3c4ec1d77c3d05caa1f0bf8fb3aae4845005c7fc',
  'chehivskiy/i18n-arabic' => '1.0.2@7539fb160b2c67538c8a0cd3ca21440d3ea781a0',
  'composer/package-versions-deprecated' => '1.11.99.4@b174585d1fe49ceed21928a945138948cb394600',
  'doctrine/annotations' => '1.13.2@5b668aef16090008790395c02c893b1ba13f7e08',
  'doctrine/cache' => '2.1.1@331b4d5dbaeab3827976273e9356b3b453c300ce',
  'doctrine/collections' => '1.6.8@1958a744696c6bb3bb0d28db2611dc11610e78af',
  'doctrine/common' => '3.1.2@a036d90c303f3163b5be8b8fde9b6755b2be4a3a',
  'doctrine/data-fixtures' => '1.5.1@f18adf13f6c81c67a88360dca359ad474523f8e3',
  'doctrine/dbal' => '2.13.4@2411a55a2a628e6d8dd598388ab13474802c7b6e',
  'doctrine/deprecations' => 'v0.5.3@9504165960a1f83cc1480e2be1dd0a0478561314',
  'doctrine/doctrine-bundle' => '2.4.3@62a188ce2192e6b3b7a2019c26c5001778818b83',
  'doctrine/doctrine-fixtures-bundle' => '3.4.0@870189619a7770f468ffb0b80925302e065a3b34',
  'doctrine/doctrine-migrations-bundle' => '3.1.1@91f0a5e2356029575f3038432cc188b12f9d5da5',
  'doctrine/event-manager' => '1.1.1@41370af6a30faa9dc0368c4a6814d596e81aba7f',
  'doctrine/inflector' => '2.0.3@9cf661f4eb38f7c881cac67c75ea9b00bf97b210',
  'doctrine/instantiator' => '1.4.0@d56bf6102915de5702778fe20f2de3b2fe570b5b',
  'doctrine/lexer' => '1.2.1@e864bbf5904cb8f5bb334f99209b48018522f042',
  'doctrine/migrations' => '3.2.1@818e31703b4fb353c0c23caa714273fc64efa675',
  'doctrine/orm' => '2.10.1@f346379c7bf39a9f46771cfd9043e0eb2dd98793',
  'doctrine/persistence' => '2.2.2@4ce4712e6dc84a156176a0fbbb11954a25c93103',
  'doctrine/sql-formatter' => '1.1.1@56070bebac6e77230ed7d306ad13528e60732871',
  'dompdf/dompdf' => 'v1.0.2@8768448244967a46d6e67b891d30878e0e15d25c',
  'fig/link-util' => '1.1.2@5d7b8d04ed3393b4b59968ca1e906fb7186d81e8',
  'firebase/php-jwt' => 'v5.4.0@d2113d9b2e0e349796e72d2a63cf9319100382d2',
  'fpdf/fpdf' => '1.83.2@6aa31c9b70a3aef2a63f79144fec79b62bad4bb0',
  'friendsofphp/proxy-manager-lts' => 'v1.0.5@006aa5d32f887a4db4353b13b5b5095613e0611f',
  'guzzlehttp/guzzle' => '6.5.5@9d4290de1cfd701f38099ef7e183b64b4b7b0c5e',
  'guzzlehttp/promises' => '1.5.1@fe752aedc9fd8fcca3fe7ad05d419d32998a06da',
  'guzzlehttp/psr7' => '1.8.3@1afdd860a2566ed3c2b0b4a3de6e23434a79ec85',
  'ismaeltoe/osms' => 'v2.0.1@1d7623be0ca30d204fa841dc55b431e72ff9c59f',
  'jeroendesloovere/vcard' => 'v1.7.3@2b8b6190c613d368b8cb6552e59cf6e6e7d0aea9',
  'jonasarts/tcpdf-bundle' => 'v5.0.0@a17518ec09650f8842db853486f69c2d29c23994',
  'khaled.alshamaa/ar-php' => 'v6.2.0@a3eb0dfe14ea9ba0fa291673c52ae7a75749e35a',
  'laminas/laminas-code' => '3.4.1@1cb8f203389ab1482bf89c0e70a04849bacd7766',
  'laminas/laminas-escaper' => '2.9.0@891ad70986729e20ed2e86355fcf93c9dc238a5f',
  'laminas/laminas-eventmanager' => '3.4.0@a93fd278c97b2d41ebbce5ba048a24e3e6f580ba',
  'laminas/laminas-zendframework-bridge' => '1.4.0@bf180a382393e7db5c1e8d0f2ec0c4af9c724baf',
  'league/csv' => '9.7.2@8544655c460fd01eed0ad258e514488d4b388645',
  'mediumart/orange-sms' => '1.1.3@62aff9d24c326663fa8b9e742fa3f15c9df20945',
  'messagebird/php-rest-api' => 'v2.1.0@3d22eb0b0572fb7e95e09469da1e287c974b2b12',
  'mpdf/mpdf' => 'v8.0.13@42f145615cfe830fd432474da1d2e1f927efe402',
  'myclabs/deep-copy' => '1.10.2@776f831124e9c62e1a2c601ecc52e776d8bb7220',
  'nelmio/cors-bundle' => '2.1.1@0b964b665016dfb61dd0fd2bb8c24afb1ae45a93',
  'nikic/php-parser' => 'v4.13.0@50953a2691a922aa1769461637869a0a2faa3f53',
  'paragonie/random_compat' => 'v9.99.100@996434e5492cb4c3edcb9168db6fbb1359ef965a',
  'phenx/php-font-lib' => '0.5.2@ca6ad461f032145fff5971b5985e5af9e7fa88d8',
  'phenx/php-svg-lib' => 'v0.3.3@5fa61b65e612ce1ae15f69b3d223cb14ecc60e32',
  'phpdocumentor/reflection-common' => '2.2.0@1d01c49d4ed62f25aa84a747ad35d5a16924662b',
  'phpdocumentor/reflection-docblock' => '5.2.2@069a785b2141f5bcf49f3e353548dc1cce6df556',
  'phpdocumentor/type-resolver' => '1.5.1@a12f7e301eb7258bb68acd89d4aefa05c2906cae',
  'phpoffice/phpword' => '0.18.2@aca10785cf68dc95d7f6fac4fe854979fef3f8db',
  'psr/cache' => '1.0.1@d11b50ad223250cf17b86e38383413f5a6764bf8',
  'psr/container' => '1.1.1@8622567409010282b7aeebe4bb841fe98b58dcaf',
  'psr/event-dispatcher' => '1.0.0@dbefd12671e8a14ec7f180cab83036ed26714bb0',
  'psr/http-message' => '1.0.1@f6561bf28d520154e4b0ec72be95418abe6d9363',
  'psr/link' => '1.0.0@eea8e8662d5cd3ae4517c9b864493f59fca95562',
  'psr/log' => '1.1.4@d49695b909c3b7628b6289db5479a1c204601f11',
  'qipsius/tcpdf-bundle' => '2.0.2@270667cb258b2d2a6b2b906f436765e51570e661',
  'ralouphie/getallheaders' => '3.0.3@120b605dfeb996808c31b6477290a714d356e822',
  'sabberworm/php-css-parser' => '8.3.1@d217848e1396ef962fb1997cf3e2421acba7f796',
  'setasign/fpdi' => 'v2.3.6@6231e315f73e4f62d72b73f3d6d78ff0eed93c31',
  'symfony/asset' => 'v5.3.4@9bd222a8fdd13ecca91384e403247103198f80a1',
  'symfony/cache' => 'v5.3.8@945bcebfef0aeef105de61843dd14105633ae38f',
  'symfony/cache-contracts' => 'v2.4.0@c0446463729b89dd4fa62e9aeecc80287323615d',
  'symfony/config' => 'v5.3.4@4268f3059c904c61636275182707f81645517a37',
  'symfony/console' => 'v5.3.7@8b1008344647462ae6ec57559da166c2bfa5e16a',
  'symfony/dependency-injection' => 'v5.3.8@e39c344e06a3ceab531ebeb6c077e6652c4a0829',
  'symfony/deprecation-contracts' => 'v2.4.0@5f38c8804a9e97d23e0c8d63341088cd8a22d627',
  'symfony/doctrine-bridge' => 'v5.3.8@212521017d81686bdc84a132fb5de2b03867a7e7',
  'symfony/dotenv' => 'v5.3.8@12888c9c46ac750ec5c1381db5bf3d534e7d70cb',
  'symfony/error-handler' => 'v5.3.7@3bc60d0fba00ae8d1eaa9eb5ab11a2bbdd1fc321',
  'symfony/event-dispatcher' => 'v5.3.7@ce7b20d69c66a20939d8952b617506a44d102130',
  'symfony/event-dispatcher-contracts' => 'v2.4.0@69fee1ad2332a7cbab3aca13591953da9cdb7a11',
  'symfony/expression-language' => 'v5.3.7@fe696e2669cb47507e5635223ac4b64500339658',
  'symfony/filesystem' => 'v5.3.4@343f4fe324383ca46792cae728a3b6e2f708fb32',
  'symfony/finder' => 'v5.3.7@a10000ada1e600d109a6c7632e9ac42e8bf2fb93',
  'symfony/flex' => 'v1.17.0@862e30b13ba0a171f05d9f4e3ea8b106084a3442',
  'symfony/framework-bundle' => 'v5.3.8@ea6e30a8c9534d87187375ef4ee39d48ee40dd2d',
  'symfony/http-client-contracts' => 'v2.4.0@7e82f6084d7cae521a75ef2cb5c9457bbda785f4',
  'symfony/http-foundation' => 'v5.3.7@e36c8e5502b4f3f0190c675f1c1f1248a64f04e5',
  'symfony/http-kernel' => 'v5.3.9@ceaf46a992f60e90645e7279825a830f733a17c5',
  'symfony/maker-bundle' => 'v1.34.0@c1ead8581cddeb4b2b9dcc8a3f00910093c4e5e8',
  'symfony/mime' => 'v5.3.11@dffc0684f10526db12c52fcd6238c64695426d61',
  'symfony/password-hasher' => 'v5.3.8@4bdaa0cca1fb3521bc1825160f3b5490c130bbda',
  'symfony/polyfill-intl-grapheme' => 'v1.23.1@16880ba9c5ebe3642d1995ab866db29270b36535',
  'symfony/polyfill-intl-idn' => 'v1.23.0@65bd267525e82759e7d8c4e8ceea44f398838e65',
  'symfony/polyfill-intl-normalizer' => 'v1.23.0@8590a5f561694770bdcd3f9b5c69dde6945028e8',
  'symfony/polyfill-mbstring' => 'v1.23.1@9174a3d80210dca8daa7f31fec659150bbeabfc6',
  'symfony/polyfill-php73' => 'v1.23.0@fba8933c384d6476ab14fb7b8526e5287ca7e010',
  'symfony/polyfill-php80' => 'v1.23.1@1100343ed1a92e3a38f9ae122fc0eb21602547be',
  'symfony/polyfill-php81' => 'v1.23.0@e66119f3de95efc359483f810c4c3e6436279436',
  'symfony/property-access' => 'v5.3.8@2fbab5f95ddb6b8e85f38a6a8a04a17c0acc4d66',
  'symfony/property-info' => 'v5.3.8@39de5bed8c036f76ec0457ec52908e45d5497947',
  'symfony/proxy-manager-bridge' => 'v5.3.4@76e61f33f6a34a340bf6e02211214f466e8e1dba',
  'symfony/requirements-checker' => 'v2.0.0@e3d5565eb69a4a2195905c8669f32e988c8e6be0',
  'symfony/routing' => 'v5.3.7@be865017746fe869007d94220ad3f5297951811b',
  'symfony/runtime' => 'v5.3.4@685a4a5491e25c7f2dd251d8fcca583b427ff290',
  'symfony/security-bundle' => 'v5.3.8@b755ed5d11685ba9aaa27b060250e5a57371f37f',
  'symfony/security-core' => 'v5.3.8@62e5943d042aa5fc43d44b40209aa7304f68c56e',
  'symfony/security-csrf' => 'v5.3.4@94b533195cf7fb21f3fae8ce349861c6401d969e',
  'symfony/security-guard' => 'v5.3.7@25f8d2a206505514a0ff14b16c4fb4e17a10cf18',
  'symfony/security-http' => 'v5.3.8@d499ecde6f81de42e557514626d6d5c14c0bdb78',
  'symfony/serializer' => 'v5.3.8@a877799b1e94f792208bea68295f6675027c92be',
  'symfony/service-contracts' => 'v2.4.0@f040a30e04b57fbcc9c6cbcf4dbaa96bd318b9bb',
  'symfony/stopwatch' => 'v5.3.4@b24c6a92c6db316fee69e38c80591e080e41536c',
  'symfony/string' => 'v5.3.7@8d224396e28d30f81969f083a58763b8b9ceb0a5',
  'symfony/translation-contracts' => 'v2.4.0@95c812666f3e91db75385749fe219c5e494c7f95',
  'symfony/twig-bridge' => 'v5.3.7@503e12aded4d5cbda4f8d1f3824c6a108119822f',
  'symfony/twig-bundle' => 'v5.3.4@345965b40c1847ebdbb2ab0eb98c71a98a5e167b',
  'symfony/validator' => 'v5.3.8@3a773f6f03ef2846c280e4f5b5f34cf950ead127',
  'symfony/var-dumper' => 'v5.3.8@eaaea4098be1c90c8285543e1356a09c8aa5c8da',
  'symfony/var-exporter' => 'v5.3.8@a7604de14bcf472fe8e33f758e9e5b7bf07d3b91',
  'symfony/web-link' => 'v5.3.4@0075c9949c30a61d9b9e7483686d72d261480ef1',
  'symfony/yaml' => 'v5.3.6@4500fe63dc9c6ffc32d3b1cb0448c329f9c814b7',
  'tecnickcom/tcpdf' => '6.4.2@172540dcbfdf8dc983bc2fe78feff48ff7ec1c76',
  'twig/twig' => 'v3.3.3@a27fa056df8a6384316288ca8b0fa3a35fdeb569',
  'webmozart/assert' => '1.10.0@6964c76c7804814a842473e0c8fd15bab0f18e25',
  'willdurand/negotiation' => '3.0.0@04e14f38d4edfcc974114a07d2777d90c98f3d9c',
  'symfony/polyfill-ctype' => '*@2170fcca88a0938c3288d51a1aadcdb9552c5bf4',
  'symfony/polyfill-iconv' => '*@2170fcca88a0938c3288d51a1aadcdb9552c5bf4',
  'symfony/polyfill-php72' => '*@2170fcca88a0938c3288d51a1aadcdb9552c5bf4',
  '__root__' => 'dev-main@2170fcca88a0938c3288d51a1aadcdb9552c5bf4',
);

    private function __construct()
    {
    }

    /**
     * @psalm-pure
     *
     * @psalm-suppress ImpureMethodCall we know that {@see InstalledVersions} interaction does not
     *                                  cause any side effects here.
     */
    public static function rootPackageName() : string
    {
        if (!self::composer2ApiUsable()) {
            return self::ROOT_PACKAGE_NAME;
        }

        return InstalledVersions::getRootPackage()['name'];
    }

    /**
     * @throws OutOfBoundsException If a version cannot be located.
     *
     * @psalm-param key-of<self::VERSIONS> $packageName
     * @psalm-pure
     *
     * @psalm-suppress ImpureMethodCall we know that {@see InstalledVersions} interaction does not
     *                                  cause any side effects here.
     */
    public static function getVersion(string $packageName): string
    {
        if (self::composer2ApiUsable()) {
            return InstalledVersions::getPrettyVersion($packageName)
                . '@' . InstalledVersions::getReference($packageName);
        }

        if (isset(self::VERSIONS[$packageName])) {
            return self::VERSIONS[$packageName];
        }

        throw new OutOfBoundsException(
            'Required package "' . $packageName . '" is not installed: check your ./vendor/composer/installed.json and/or ./composer.lock files'
        );
    }

    private static function composer2ApiUsable(): bool
    {
        if (!class_exists(InstalledVersions::class, false)) {
            return false;
        }

        if (method_exists(InstalledVersions::class, 'getAllRawData')) {
            $rawData = InstalledVersions::getAllRawData();
            if (count($rawData) === 1 && count($rawData[0]) === 0) {
                return false;
            }
        } else {
            $rawData = InstalledVersions::getRawData();
            if ($rawData === null || $rawData === []) {
                return false;
            }
        }

        return true;
    }
}