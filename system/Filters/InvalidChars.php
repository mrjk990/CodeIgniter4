<?php

/**
 * This file is part of CodeIgniter 4 framework.
 *
 * (c) CodeIgniter Foundation <admin@codeigniter.com>
 *
 * For the full copyright and license information, please view
 * the LICENSE file that was distributed with this source code.
 */

namespace CodeIgniter\Filters;

use CodeIgniter\HTTP\RequestInterface;
use CodeIgniter\HTTP\ResponseInterface;
use RuntimeException;

/**
 * InvalidChars filter.
 *
 * Check if user input data ($_GET, $_POST, $_COOKIE, php://input) do not contain
 * invalid characters:
 *   - invalid UTF-8 characters
 *   - control characters except line break and tab code
 */
class InvalidChars implements FilterInterface
{
    /**
     * Data source
     *
     * @var string
     */
    protected $source;

    /**
     * Check invalid characters.
     *
     * @param array|null $arguments
     *
     * @return void
     */
    public function before(RequestInterface $request, $arguments = null)
    {
        if ($request->isCLI()) {
            return;
        }

        $data = [
            'get'      => $request->getGet(),
            'post'     => $request->getPost(),
            'cookie'   => $request->getCookie(),
            'rawInput' => $request->getRawInput(),
        ];

        foreach ($data as $source => $values) {
            $this->source = $source;
            $this->checkEncoding($values);
            $this->checkControl($values);
        }
    }

    /**
     * We don't have anything to do here.
     *
     * @param array|null $arguments
     *
     * @return void
     */
    public function after(RequestInterface $request, ResponseInterface $response, $arguments = null)
    {
    }

    /**
     * Check the character encoding is valid UTF-8.
     *
     * @param array|string $value
     *
     * @return array|string
     */
    protected function checkEncoding($value)
    {
        if (is_array($value)) {
            array_map([$this, 'checkEncoding'], $value);

            return $value;
        }

        if (mb_check_encoding($value, 'UTF-8')) {
            return $value;
        }

        throw new RuntimeException(
            'Invalid UTF-8 characters in ' . $this->source . ': ' . $value
        );
    }

    /**
     * Check for the presence of control characters except line breaks and tabs.
     *
     * @param array|string $value
     *
     * @return array|string
     */
    protected function checkControl($value)
    {
        if (is_array($value)) {
            array_map([$this, 'checkControl'], $value);

            return $value;
        }

        if (preg_match('/\A[\r\n\t[:^cntrl:]]*\z/u', $value) === 1) {
            return $value;
        }

        throw new RuntimeException(
            'Invalid Control characters in ' . $this->source . ': ' . $value
        );
    }
}
