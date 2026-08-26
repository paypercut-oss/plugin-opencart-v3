<?php

namespace Paypercut\Telemetry;

/**
 * The three storage primitives every telemetry unit depends on.
 *
 * OpenCart has no transient concept, so the expiring blobs (token, queue,
 * inflight) live in a module-owned table with an `expires_at` column. The TTL
 * is a backstop, never the authority: every read re-validates against the
 * session record.
 *
 * Every query is wrapped: a missing table or a locked row must never break a
 * checkout, and the whole feature is best-effort by design.
 */
class Store
{
    const TABLE = 'paypercut_telemetry';

    const SETTING_CODE = 'paypercut_telemetry';

    const RECORD_KEY = 'paypercut_telemetry_session';

    private static $record_override = null;

    private static $lock_owners = array();

    /**
     * The durable session record.
     *
     * Kept in `oc_setting` under a code the payment settings form never writes,
     * so a settings save cannot lose it, and read for free from the settings
     * OpenCart already loads on every request.
     */
    public static function record()
    {
        if (self::$record_override !== null) {
            return self::$record_override;
        }

        $record = Context::setting(self::RECORD_KEY, array());

        return is_array($record) ? $record : array();
    }

    public static function putRecord(array $record)
    {
        self::$record_override = $record;

        $db = Context::db();

        if ($db === null) {
            return;
        }

        try {
            self::deleteRecordRow($db);

            $db->query(
                "INSERT INTO `" . DB_PREFIX . "setting`
                SET store_id = '0',
                    `code` = '" . $db->escape(self::SETTING_CODE) . "',
                    `key` = '" . $db->escape(self::RECORD_KEY) . "',
                    `value` = '" . $db->escape(json_encode($record)) . "',
                    serialized = '1'"
            );
        } catch (\Exception $exception) {
            self::$record_override = null;
        }
    }

    public static function deleteRecord()
    {
        self::$record_override = array();

        $db = Context::db();

        if ($db === null) {
            return;
        }

        try {
            self::deleteRecordRow($db);
        } catch (\Exception $exception) {
            self::$record_override = null;
        }
    }

    /**
     * A stored blob, or null when absent or expired.
     */
    public static function get($key)
    {
        $db = Context::db();

        if ($db === null) {
            return null;
        }

        try {
            $query = $db->query(
                "SELECT `value`, `expires_at` FROM `" . DB_PREFIX . self::TABLE . "`
                WHERE `telemetry_key` = '" . $db->escape($key) . "' LIMIT 1"
            );
        } catch (\Exception $exception) {
            return null;
        }

        if (!$query->num_rows) {
            return null;
        }

        $expires_at = (int)$query->row['expires_at'];

        if ($expires_at > 0 && $expires_at <= time()) {
            return null;
        }

        $value = json_decode($query->row['value'], true);

        return is_array($value) ? $value : null;
    }

    /**
     * Write a blob. An empty value deletes the row rather than leaving one behind.
     */
    public static function put($key, array $value, $ttl = 0)
    {
        if (empty($value)) {
            self::delete($key);
            return;
        }

        $db = Context::db();

        if ($db === null) {
            return;
        }

        $expires_at = $ttl > 0 ? time() + (int)$ttl : 0;

        try {
            $db->query(
                "REPLACE INTO `" . DB_PREFIX . self::TABLE . "`
                SET `telemetry_key` = '" . $db->escape($key) . "',
                    `value` = '" . $db->escape(json_encode($value)) . "',
                    `expires_at` = '" . (int)$expires_at . "',
                    `updated_at` = NOW()"
            );
        } catch (\Exception $exception) {
            // Best effort: a diagnostic buffer is never worth an exception on
            // the checkout path.
        }
    }

    public static function delete($key)
    {
        $db = Context::db();

        if ($db === null) {
            return;
        }

        try {
            $db->query(
                "DELETE FROM `" . DB_PREFIX . self::TABLE . "`
                WHERE `telemetry_key` = '" . $db->escape($key) . "'"
            );
        } catch (\Exception $exception) {
            // See put().
        }
    }

    /**
     * Take a lock that genuinely fails under contention.
     *
     * A read-then-write would hand both callers the lock; the primary key on
     * `telemetry_key` plus INSERT IGNORE means exactly one caller sees a row
     * affected.
     */
    public static function claimLock($name, $ttl)
    {
        $db = Context::db();

        if ($db === null) {
            return false;
        }

        $owner = self::owner();

        if (self::insertLock($name, $owner, $ttl)) {
            self::$lock_owners[$name] = $owner;

            return true;
        }

        if (!self::lockIsStale($name, $ttl)) {
            return false;
        }

        // An abandoned lock: clear it and try exactly once more, so a crashed
        // request cannot block the feature forever and a live holder is never
        // displaced by an unbounded retry loop.
        self::delete($name);

        if (!self::insertLock($name, $owner, $ttl)) {
            return false;
        }

        self::$lock_owners[$name] = $owner;

        return true;
    }

    /**
     * Release a lock only if this request is still the holder: a request that
     * overran the TTL must not delete the new holder's lock on the way out.
     */
    public static function releaseLock($name)
    {
        if (!isset(self::$lock_owners[$name])) {
            return;
        }

        $owner = self::$lock_owners[$name];
        unset(self::$lock_owners[$name]);

        $held = self::readRaw($name);

        if (is_array($held) && isset($held['owner']) && $held['owner'] !== $owner) {
            return;
        }

        self::delete($name);
    }

    /**
     * Create the telemetry table if it is not there yet.
     *
     * Called from install() and from the admin paths that need it, because an
     * upgrade that copies files over never re-runs install().
     */
    public static function ensureSchema()
    {
        $db = Context::db();

        if ($db === null) {
            return;
        }

        try {
            $db->query(
                "CREATE TABLE IF NOT EXISTS `" . DB_PREFIX . self::TABLE . "` (
                    `telemetry_key` varchar(64) NOT NULL,
                    `value` longtext NOT NULL,
                    `expires_at` int(11) NOT NULL DEFAULT '0',
                    `updated_at` datetime NOT NULL,
                    PRIMARY KEY (`telemetry_key`)
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci;"
            );
        } catch (\Exception $exception) {
            // Nothing to do: every read and write below degrades to "no session".
        }
    }

    /**
     * Delete every stored key. Used by uninstall.
     */
    public static function purge()
    {
        $db = Context::db();

        if ($db === null) {
            return;
        }

        try {
            $db->query("DELETE FROM `" . DB_PREFIX . self::TABLE . "`");
        } catch (\Exception $exception) {
            // The table may never have been created.
        }

        self::deleteRecord();
    }

    private static function insertLock($name, $owner, $ttl)
    {
        $db = Context::db();
        $value = json_encode(array('owner' => $owner, 'at' => time()));

        try {
            $db->query(
                "INSERT IGNORE INTO `" . DB_PREFIX . self::TABLE . "`
                SET `telemetry_key` = '" . $db->escape($name) . "',
                    `value` = '" . $db->escape($value) . "',
                    `expires_at` = '" . (int)(time() + $ttl) . "',
                    `updated_at` = NOW()"
            );

            return (int)$db->countAffected() === 1;
        } catch (\Exception $exception) {
            return false;
        }
    }

    private static function lockIsStale($name, $ttl)
    {
        $held = self::readRaw($name);

        if (!is_array($held) || !isset($held['at'])) {
            return true;
        }

        return (time() - (int)$held['at']) > $ttl;
    }

    /**
     * Read a row ignoring its expiry, so a stale lock can still be inspected.
     */
    private static function readRaw($key)
    {
        $db = Context::db();

        if ($db === null) {
            return null;
        }

        try {
            $query = $db->query(
                "SELECT `value` FROM `" . DB_PREFIX . self::TABLE . "`
                WHERE `telemetry_key` = '" . $db->escape($key) . "' LIMIT 1"
            );
        } catch (\Exception $exception) {
            return null;
        }

        if (!$query->num_rows) {
            return null;
        }

        $value = json_decode($query->row['value'], true);

        return is_array($value) ? $value : null;
    }

    private static function deleteRecordRow($db)
    {
        $db->query(
            "DELETE FROM `" . DB_PREFIX . "setting`
            WHERE store_id = '0'
            AND `code` = '" . $db->escape(self::SETTING_CODE) . "'
            AND `key` = '" . $db->escape(self::RECORD_KEY) . "'"
        );
    }

    private static function owner()
    {
        if (function_exists('random_bytes')) {
            try {
                return bin2hex(random_bytes(8));
            } catch (\Exception $exception) {
                // Fall through to the weaker source below.
            }
        }

        return substr(md5(uniqid('', true)), 0, 16);
    }
}
