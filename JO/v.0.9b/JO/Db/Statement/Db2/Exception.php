<?php
/**
 * JO Framework
 *
 * LICENSE
 *
 * This source file is subject to the new BSD license that is bundled
 * with this package in the file LICENSE.txt.
 * It is also available through the world-wide-web at this URL:
 * http://framework.zend.com/license/new-bsd
 * If you did not receive a copy of the license and are unable to
 * obtain it through the world-wide-web, please send an email
 * to license@zend.com so we can send you a copy immediately.
 *
 * @category   JO
 * @package    JO_Db
 * @subpackage Statement
 * @copyright  Copyright (c) 2005-2010 JO Technologies USA Inc. (http://www.zend.com)
 * @license    http://framework.zend.com/license/new-bsd     New BSD License
 * @version    $Id: Exception.php 20096 2010-01-06 02:05:09Z bkarwin $
 */

/**
 * JO_Db_Statement_Exception
 */
require_once 'JO/Db/Statement/Exception.php';

/**
 * @package    JO_Db
 * @subpackage Statement
 * @copyright  Copyright (c) 2005-2010 JO Technologies USA Inc. (http://www.zend.com)
 * @license    http://framework.zend.com/license/new-bsd     New BSD License
 */

class JO_Db_Statement_Db2_Exception extends JO_Db_Statement_Exception
{
    /**
     * @var string
     */
    protected $code = '00000';

    /**
     * @var string
     */
    protected $message = 'unknown exception';

    /**
     * @param string $msg
     * @param string $state
     */
    function __construct($msg = 'unknown exception', $state = '00000')
    {
        $this->message = $msg;
        $this->code = $state;
    }

}

