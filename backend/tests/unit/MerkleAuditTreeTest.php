<?php

use PHPUnit\Framework\TestCase;
use Atom\Vault\MerkleAuditTree;

/**
 * Phase 33 — MerkleAuditTree unit tests (5 tests).
 */
class MerkleAuditTreeTest extends TestCase
{
    private MerkleAuditTree $tree;

    protected function setUp(): void
    {
        $this->tree = new MerkleAuditTree();
    }

    public function testAddLeafAndComputeRootHash(): void
    {
        $hash1 = $this->tree->addLeaf('record_1');
        $this->assertNotEmpty($hash1);
        $this->assertSame($hash1, $this->tree->getRootHash());

        $hash2 = $this->tree->addLeaf('record_2');
        $root = $this->tree->getRootHash();

        $expectedRoot = hash('sha256', $hash1 . $hash2);
        $this->assertSame($expectedRoot, $root);
    }

    public function testDeterministicRootHashAcrossTrees(): void
    {
        $tree1 = new MerkleAuditTree();
        $tree1->addLeaf('data_a');
        $tree1->addLeaf('data_b');
        $tree1->addLeaf('data_c');

        $tree2 = new MerkleAuditTree();
        $tree2->addLeaf('data_a');
        $tree2->addLeaf('data_b');
        $tree2->addLeaf('data_c');

        $this->assertSame($tree1->getRootHash(), $tree2->getRootHash());
    }

    public function testTamperedLeafProducesDifferentRoot(): void
    {
        $tree1 = new MerkleAuditTree();
        $tree1->addLeaf('clean_data');

        $tree2 = new MerkleAuditTree();
        $tree2->addLeaf('tampered_data');

        $this->assertNotSame($tree1->getRootHash(), $tree2->getRootHash());
    }

    public function testVerifyLeafMembership(): void
    {
        $h1 = $this->tree->addLeaf('entry_alpha');
        $h2 = $this->tree->addLeaf('entry_beta');

        $this->assertTrue($this->tree->verifyLeaf($h1));
        $this->assertTrue($this->tree->verifyLeaf($h2));
        $this->assertFalse($this->tree->verifyLeaf(hash('sha256', 'unknown_entry')));
    }

    public function testEmptyTreeRootHashIsNull(): void
    {
        $this->assertNull($this->tree->getRootHash());
        $this->assertEmpty($this->tree->getLeaves());
    }
}
