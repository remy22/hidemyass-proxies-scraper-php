<?php

/**
 * Class for collecting proxies list from hidemyass[dot]com
 * Copyright (C) 2016  Bohdan Manko <mailmanbo@gmail.com>
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.

 * You should have received a copy of the GNU General Public License
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 */

/**
 * ProxyList
 *
 * PHP version 7
 *
 * @author   Bohdan Manko <mailmanbo@gmail.com>
 * @license  http://www.gnu.org/licenses/ GPL v3
 * @link     https://github.com/mkbodanu4/hidemyass-proxies-scraper-php
 */

class ProxyList
{
    private $base = "http://proxylist.hidemyass.com/";
    private $params = array(
        array('ac', 'on'), //all countries [remove line below to exclude. don't forget to remove this line if at least one country excluded]
        array('c[]', 'Mexico'),
        array('c[]', 'Brazil'),
        array('c[]', 'China'),
        array('c[]', 'United+States'),
        array('c[]', 'Korea,+Republic+of'),
        array('c[]', 'Trinidad+and+Tobago'),
        array('c[]', 'Russian+Federation'),
        array('c[]', 'Hong+Kong'),
        array('c[]', 'Venezuela'),
        array('c[]', 'Netherlands'),
        array('c[]', 'France'),
        array('c[]', 'Indonesia'),
        array('c[]', 'Germany'),
        array('c[]', 'Viet+Nam'),
        array('c[]', 'Austria'),
        array('c[]', 'United+Kingdom'),
        array('c[]', 'Switzerland'),
        array('c[]', 'Puerto+Rico'),
        array('c[]', 'Kazakhstan'),
        array('c[]', 'Taiwan'),
        array('c[]', 'Chile'),
        array('c[]', 'India'),
        array('c[]', 'Taiwan'),
        array('c[]', 'Thailand'),
        array('c[]', 'Europe'),
        array('c[]', 'Turkey'),
        array('c[]', 'Norway'),
        array('c[]', 'Ecuador'),
        array('c[]', 'Malaysia'),
        array('c[]', 'Japan'),
        array('c[]', 'Moldova,+Republic+of'),
        array('c[]', 'New+Zealand'),
        array('c[]', 'Nigeria'),
        array('c[]', 'Armenia'),
        array('c[]', 'Belarus'),
        array('c[]', 'Macedonia'),
        array('c[]', 'Bulgaria'),
        array('c[]', 'Colombia'),
        array('c[]', 'Argentina'),
        array('c[]', 'Denmark'),
        array('c[]', 'Croatia'),
        array('c[]', 'Sweden'),
        array('c[]', 'Slovakia'),
        array('c[]', 'Panama'),
        array('c[]', 'Israel'),
        array('c[]', 'Egypt'),
        array('c[]', 'Czech+Republic'),
        array('c[]', 'Paraguay'),
        array('c[]', 'Bangladesh'),
        array('c[]', 'South+Africa'),
        array('c[]', 'Kenya'),
        array('c[]', 'Reunion'),
        array('p', '80,22'), //exclude this ports, comma separated string, if none - empty string
        array('pr[]', 0), //Protocol - HTTP [remove any of lines below to exclude]
        array('pr[]', 1), //Protocol - HTTPS
        array('pr[]', 2), //Protocol - SOCKS4/SOCKS5
        array('a[]', 0), //Anonymity Level - None [remove any of lines below to exclude]
        array('a[]', 1), //Anonymity Level - Low
        array('a[]', 2), //Anonymity Level - Medium
        array('a[]', 3), //Anonymity Level - High
        array('a[]', 4), //Anonymity Level - High + KA
        array('pl', 'on'), //Planetlab include: "on"/"off"
        array('sp[]', 1), //Speed - slow [remove any of lines below to exclude]
        array('sp[]', 2), //Speed - medium
        array('sp[]', 3), //Speed - fast
        array('ct[]', 1), //Connection time - slow [remove any of lines below to exclude]
        array('ct[]', 2), //Connection time - medium
        array('ct[]', 3), //Connection time - fast
        array('s', 0), //Sort by: 0 - Date tested, 1 - Response time, 2 - Connection time, 3 - Country A-Z
        array('o', 0), // Order: 0 - ASC, 1 - DESC
        array('pp', 3), //Per page: 0 - 10, 1 - 25, 2 - 50, 3 - 100
        array('sortBy', 'date') //Sort by: "date" - Date tested, "response_time" - Response time, "connection_time" - Connection time, "country" - Country A-Z
    );
    private $cookies;
    private $error = "";
    private $data = null;
    private $info = null;

    public function __construct($params = false)
    {
        if($params) {
            $this->params = $params;
        }
    }

    /**
     * Init cookies in tmpfs
     */
    private function init_cookies()
    {
        $this->cookies = tempnam ("/tmp", "CURLCOOKIE");
        if(!$this->cookies || !file_exists($this->cookies)) {
            $this->error = "Can't create temporary cookies file";
        }
    }

    /**
     * Remove cookies file from tmpfs
     */
    private function close_cookies()
    {
        if(file_exists($this->cookies)) {
            unlink($this->cookies);
        }
    }

    /**
     * Get error string
     */
    public function get_error()
    {
        return $this->error;
    }


    /**
     * Get page contents via cURL
     * @param string $url content URL
     * @param array $info request details
     * @param array|bool $post post data or false
     * @param string|bool $cookies cookies filename
     * @param string|bool $referer referer header value
     * @param bool $allow_redirect allow redirects
     * @param bool $header return headers
     * @return bool|mixed result
     */
    private function get_contents($url, &$info, $post = false, $cookies = false, $referer = false, $allow_redirect = false, $header = false)
    {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
        if ($post && is_array($post)) {
            curl_setopt($ch, CURLOPT_POST, true);
            for ($i = 0; $i < count($post); $i++) if (is_array($post[$i])) $post[$i] = implode('=', $post[$i]); else break;
            curl_setopt($ch, CURLOPT_POSTFIELDS, implode('&', $post));
        }
        if ($cookies) {
            curl_setopt($ch, CURLOPT_COOKIE, $cookies);
            curl_setopt($ch, CURLOPT_COOKIEFILE, $cookies);
            curl_setopt($ch, CURLOPT_COOKIEJAR, $cookies);
        }
        curl_setopt($ch, CURLOPT_USERAGENT, '"Mozilla/5.0 (X11; Linux x86_64; rv:46.0) Gecko/20100101 Firefox/46.0"');
        curl_setopt($ch, CURLOPT_HEADER, $header);
        curl_setopt($ch, CURLINFO_HEADER_OUT, $header);
        curl_setopt($ch, CURLOPT_HTTPHEADER, array(
            'DNT: 1',
            'Referer: ' . $referer,
            'Content-Type: application/x-www-form-urlencoded; charset=UTF-8',
            'X-Requested-With: XMLHttpRequest',
            'Connection: keep-alive'
        ));

        try {
            $output = curl_exec($ch);
            $info = curl_getinfo($ch);
        } catch (Exception $ex) {
            die($ex->getMessage());
        }
        /* php 5.5 and above
        finally {
            curl_close($ch);
        }
        */
        if ($ch) curl_close($ch);

        if ($allow_redirect && ($info['http_code'] == 301 || $info['http_code'] == 302)) {
            $url = $info['redirect_url'];
            $url_parsed = parse_url($url);
            return (isset($url_parsed)) ? get_contents($url, $post, $cookies) : false;
        }

        return $output;
    }

    /**
     * Get raw JSON from hidemyass
     * @return bool|mixed|null
     */
    public function get_raw()
    {
        $this->init_cookies();
        try {
            $this->data = $this->get_contents($this->base, $this->info, $this->params, $this->cookies, $this->base);
            $this->close_cookies();
            return $this->data;
        } catch (Exception $e) {
            $this->error .= $e->getMessage()."\r\n";
            $this->close_cookies();
            return false;
        }
    }

    /**
     * Get parsed data as object
     * @return bool|object
     */
    public function get()
    {
        if(!($this->data && $this->info)) {
            $this->get_raw();
        }

        //if data available and HTTP code of result = 200 (success)
        if ($this->data && $this->info && $this->info['http_code'] == 200) {
            $json = null;

            //try to parse json
            try {
                $json = json_decode($this->data);
            } catch (Exception $e) {
                $this->error .= $e->getMessage()."\r\n";
            }

            //if json parsed - read needed data and prepare object with proxies list
            if ($json) {
                $table = $json->table;

                //get all proxies rows
                $rows = array();
                preg_match_all("#\<tr class\=\"[\w\d _-]{0,}\" rel=\"[\d]{1,}\">(.*?)\</tr\>#s", $table, $rows);

                if($rows && $rows[1] && count($rows[1])) {
                    $table = $rows[1];
                    $data = array();

                    foreach ($table as $tr) {
                        //get columns
                        $cols = array();
                        preg_match_all("#\<td(.*?)\>(.*?)\</td\>#s", $tr, $cols);

                        if($cols && $cols[2] && count($cols[2])) {
                            //get "hidden" blocks class names
                            preg_match_all("#\<style>(.*?)\</style\>#s", $cols[2][1], $ip_style);
                            preg_match_all("/\.(.*?)\{display\:none\}/Ui", $ip_style[1][0], $ip_styles);

                            //remove "hidden" blocks
                            if(count($ip_styles[1]) > 0) {
                                foreach($ip_styles[1] as $style) {
                                    $cols[2][1] = preg_replace("/\<[spandiv]{1,} class\=\"".$style."\"\>[\d]{1,}\<\/[spandiv]{1,}\>/Ui","",$cols[2][1]);
                                }
                            }

                            //remove style
                            $ip = preg_replace("/(\<style\>[A-Za-z0-9\t\s -_.,:{}]{1,}\<\/style\>)/Ui","", $cols[2][1]);
                            //remove other "hidden" blocks
                            $ip = preg_replace("/\<[spandiv]{1,} style\=\"display\:none\"\>[\d]{1,}\<\/[spandiv]{1,}\>/Ui","",$ip);

                            //get speed and connection time from style
                            preg_match("/width\:[ ]{0,}([\d]{1,})\%/Ui", $cols[2][4], $speed);
                            preg_match("/width\:[ ]{0,}([\d]{1,})\%/Ui", $cols[2][5], $time);

                            //parse other data
                            $data[] = array(
                                'update' => trim(strip_tags($cols[2][0])),
                                'ip' => trim(strip_tags($ip)),
                                'port' => trim(strip_tags($cols[2][2])),
                                'country' => trim(strip_tags($cols[2][3])),
                                'speed' => trim(strip_tags($speed[1])),
                                'time' => trim(strip_tags($time[1])),
                                'type' => trim(strip_tags($cols[2][6])),
                                'anon' => trim(strip_tags($cols[2][7])),
                            );
                        }
                    }

                    return $data;
                } else {
                    $this->error = "Empty response";
                    return false;
                }
            } else {
                $this->error = "Can't decode JSON";
                return false;
            }
        } else {
            $this->error = "No response, please check your server";
            return false;
        }
    }

}