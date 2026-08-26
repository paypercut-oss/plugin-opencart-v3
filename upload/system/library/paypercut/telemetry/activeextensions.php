<?php

namespace Paypercut\Telemetry;

/**
 * The store's installed extensions, for correlating a failure with a conflict.
 *
 * Codes and versions only. OpenCart records no version for an extension, so a
 * version appears only where an OCMod modification of the same code carries
 * one; everything else reports an empty version rather than a guess.
 */
class ActiveExtensions
{
    /**
     * @return array code => version, sorted by code.
     */
    public static function values()
    {
        $db = Context::db();

        if ($db === null) {
            return array();
        }

        $extensions = array();

        try {
            $modifications = $db->query("SELECT `code`, `version` FROM `" . DB_PREFIX . "modification` WHERE status = '1'");

            foreach ($modifications->rows as $row) {
                $code = Event::text((string)$row['code']);

                if ($code !== '') {
                    $extensions[$code] = Event::text((string)$row['version']);
                }
            }

            $installed = $db->query("SELECT `type`, `code` FROM `" . DB_PREFIX . "extension`");

            foreach ($installed->rows as $row) {
                $code = Event::text((string)$row['type'] . '.' . (string)$row['code']);

                if ($code !== '' && !isset($extensions[$code])) {
                    $extensions[$code] = '';
                }
            }
        } catch (\Exception $exception) {
            return $extensions;
        }

        ksort($extensions);

        return $extensions;
    }
}
