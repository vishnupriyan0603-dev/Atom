<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

/**
 * Adds per-user data isolation to the web chat tables.
 *
 * Existing rows are backfilled to the most recently registered user
 * (the current SPA session) so historical chats remain visible.
 */
class AddUserIdToChatsMessages extends Migration
{
    public function up()
    {
        if (!$this->db->fieldExists('user_id', 'chats')) {
            $this->forge->addColumn('chats', [
                'user_id' => [
                    'type'       => 'INT',
                    'unsigned'   => true,
                    'null'       => true,
                    'after'      => 'title',
                ],
            ]);
            $this->forge->addKey('user_id', false, false, 'chats_user_id');
        }

        if (!$this->db->fieldExists('user_id', 'messages')) {
            $this->forge->addColumn('messages', [
                'user_id' => [
                    'type'       => 'INT',
                    'unsigned'   => true,
                    'null'       => true,
                    'after'      => 'chat_id',
                ],
            ]);
            $this->forge->addKey('user_id', false, false, 'messages_user_id');
        }

        // Backfill existing rows to the most recently registered user
        try {
            if ($this->db->tableExists('users')) {
                $target = $this->db->query(
                    "SELECT id FROM " . $this->db->prefixTable('users') . " ORDER BY id DESC LIMIT 1"
                )->getRow();

                if ($target) {
                    $userId = (int) $target->id;
                    $this->db->query("UPDATE " . $this->db->prefixTable('chats') . " SET user_id = ? WHERE user_id IS NULL", [$userId]);
                }
            }
        } catch (\Throwable $e) {
            // Ignore backfill if users table absent during fresh unit test migration
        }
    }

    public function down()
    {
        if ($this->db->fieldExists('user_id', 'chats')) {
            $this->forge->dropColumn('chats', 'user_id');
        }
        if ($this->db->fieldExists('user_id', 'messages')) {
            $this->forge->dropColumn('messages', 'user_id');
        }
    }
}
