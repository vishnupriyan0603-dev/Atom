<?php

use PHPUnit\Framework\TestCase;
use Atom\Search\CodeChunkSegmenter;

/**
 * Phase 39 — CodeChunkSegmenter unit tests (5 tests).
 */
class CodeChunkSegmenterTest extends TestCase
{
    private CodeChunkSegmenter $segmenter;

    protected function setUp(): void
    {
        $this->segmenter = new CodeChunkSegmenter();
    }

    public function testSegmentExtractsClassAndFunctionChunks(): void
    {
        $code = <<<'PHP'
class UserService {
    public function getUserById(int $id) {
        return ['id' => $id];
    }

    public function deleteUser(int $id) {
        return true;
    }
}
PHP;
        $chunks = $this->segmenter->segment($code, 'src/UserService.php');

        $this->assertGreaterThanOrEqual(2, count($chunks));
        $this->assertSame('src/UserService.php', $chunks[0]['file']);
    }

    public function testChunkLineCoordinatesAreAccurate(): void
    {
        $code = "function alpha() {}\nfunction beta() {}";
        $chunks = $this->segmenter->segment($code, 'test.php');

        $this->assertCount(2, $chunks);
        $this->assertSame(1, $chunks[0]['start_line']);
        $this->assertSame(1, $chunks[0]['end_line']);
        $this->assertSame(2, $chunks[1]['start_line']);
        $this->assertSame(2, $chunks[1]['end_line']);
    }

    public function testGlobalScriptWithoutFunctionsReturnsSingleChunk(): void
    {
        $code = "echo 'Hello World';\n\$x = 10;\n\$y = 20;";
        $chunks = $this->segmenter->segment($code, 'script.php');

        $this->assertCount(1, $chunks);
        $this->assertSame('global_scope', $chunks[0]['symbol']);
    }

    public function testAsyncMethodMatching(): void
    {
        $code = "async function fetchData() { return true; }";
        $chunks = $this->segmenter->segment($code, 'app.js');

        $this->assertCount(1, $chunks);
        $this->assertSame('fetchData', $chunks[0]['symbol']);
    }

    public function testInterfaceDeclarationMatching(): void
    {
        $code = "interface KeyStorageInterface {\n    public function getKey();\n}";
        $chunks = $this->segmenter->segment($code, 'KeyStorage.php');

        $this->assertGreaterThanOrEqual(1, count($chunks));
        $this->assertSame('KeyStorageInterface', $chunks[0]['symbol']);
    }
}
