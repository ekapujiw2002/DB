<?php
    class DB
    {
        private static $joiners = array(
            ',' => ',',
            '&' => ' AND ',
            '|' => ' OR ',
        );

        private static $instance = null;

        final private function __construct() {}
        final private function __clone() {}

        public static function instance() {
            if (self::$instance === null) {
                try {
                    self::$instance = new PDO(
                        'mysql:host='.DB_HOST.';dbname='.DB_NAME,
                        DB_USER,
                        DB_PASS
                    );
                    self::$instance->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
                }
                catch (PDOException $e) {
                    die('Database connection could not be established.');
                }
            }

            return self::$instance;
        }

        public static function q($query) {
            if (func_num_args() == 1) {
                return self::instance()->query($query);
            }

            $args = func_get_args();
            return self::instance()->query(self::autoQuote(array_shift($args), $args));
        }

        public static function x($query) {
            if (func_num_args() == 1) {
                return self::instance()->exec($query);
            }

            $args = func_get_args();
            return self::instance()->exec(self::autoQuote(array_shift($args), $args));
        }

        public static function autoQuote($query, array $args) {
            $i = strlen($query);
            $c = count($args);

            if (substr_count($query, '?') != $c) {
                throw new UnexpectedValueException('Wrong parameter count: Number of placeholders and parameters does not match');
            }

            while ($c--) {
                while ($i-- && $query[$i] != '?');

                if (isset($query[$i+1]) && isset(self::$joiners[$query[$i+1]])) {
                    foreach ($args[$c] as $key => &$value) {
                        $value = '`' . $key . '`=' . self::instance()->quote($value);
                    }

                    $query = substr_replace($query, implode(self::$joiners[$query[$i+1]], $args[$c]), $i, 2);
                    continue;
                }

                if (null === $args[$c]) {
                    $replace = 'NULL';
                }
                elseif (is_array($args[$c])) {
                    foreach ($args[$c] as &$value) {
                        $value = self::instance()->quote($value);
                    }

                    $replace = '(' . implode(',', $args[$c]) . ')';
                }
                else {
                    $replace = self::instance()->quote($args[$c]);
                }

                $query = substr_replace($query, $replace, $i, 1);
            }

            return $query;
        }

        public static function __callStatic($method, $args) {
            return call_user_func_array(array(self::instance(), $method), $args);
        }
    }