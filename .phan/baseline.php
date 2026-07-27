<?php
/**
 * This is an automatically generated baseline for Phan issues.
 * When Phan is invoked with --load-baseline=path/to/baseline.php,
 * The pre-existing issues listed in this file won't be emitted.
 *
 * This file can be updated by invoking Phan with --save-baseline=path/to/baseline.php
 * (can be combined with --load-baseline)
 */
return [
    // # Issue statistics:
    // PhanTypePossiblyInvalidDimOffset : 7 occurrences
    // PhanUndeclaredGlobalVariable : 7 occurrences
    // PhanTypeMismatchArgument : 3 occurrences
    // PhanTypeMismatchProperty : 3 occurrences
    // PhanPluginDuplicateConditionalNullCoalescing : 2 occurrences
    // MediaWikiNoIssetIfDefined : 1 occurrence
    // PhanParamTooMany : 1 occurrence
    // PhanThrowTypeAbsent : 1 occurrence
    // PhanTypeMismatchArgumentProbablyReal : 1 occurrence

    'file_suppressions' => [
        'demo/api_requests.php' => [
            'PhanUndeclaredGlobalVariable' => ['demo/api_requests.php']
        ],
        'demo/callback.php' => [
            'PhanUndeclaredGlobalVariable' => ['demo/callback.php']
        ],
        'src/Client.php' => [
            'PhanParamTooMany' => ['\\MediaWiki\\OAuthClient\\Client::newFromKeyAndSecret'],
            'PhanTypeMismatchArgument' => ['\\MediaWiki\\OAuthClient\\Client::makeOAuthCall'],
            'PhanTypeMismatchArgumentProbablyReal' => ['\\MediaWiki\\OAuthClient\\Client::initiate'],
            'PhanTypePossiblyInvalidDimOffset' => ['\\MediaWiki\\OAuthClient\\Client::makeOAuthCall']
        ],
        'src/ClientConfig.php' => [
            'PhanTypePossiblyInvalidDimOffset' => ['\\MediaWiki\\OAuthClient\\ClientConfig::__construct']
        ],
        'src/Request.php' => [
            'PhanThrowTypeAbsent' => ['\\MediaWiki\\OAuthClient\\Request::toHeader'],
            'PhanTypeMismatchProperty' => ['\\MediaWiki\\OAuthClient\\Request::setParameter']
        ],
        'src/Util.php' => [
            'MediaWikiNoIssetIfDefined' => ['\\MediaWiki\\OAuthClient\\Util::parseParameters']
        ],
        'tests/RequestTest.php' => [
            'PhanPluginDuplicateConditionalNullCoalescing' => ['\\MediaWiki\\OAuthClient\\Test\\RequestTest::buildRequest'],
            'PhanTypeMismatchArgument' => ['\\MediaWiki\\OAuthClient\\Test\\RequestTest::testCanGetSingleParameter', '\\MediaWiki\\OAuthClient\\Test\\RequestTest::testGetAllParameters'],
            'PhanTypePossiblyInvalidDimOffset' => ['\\MediaWiki\\OAuthClient\\Test\\RequestTest::buildRequest']
        ],
    ],
    // 'directory_suppressions' => ['src/directory_name' => ['PhanIssueName1', 'PhanIssueName2']] can be manually added if needed.
    // (directory_suppressions will currently be ignored by subsequent calls to --save-baseline, but may be preserved in future Phan releases)
];
