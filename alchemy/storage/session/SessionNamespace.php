<?php
namespace alchemy\storage\session;
/**
 * Session's namespace
 * Provides functionality to expire variables in it
 */
class SessionNamespace implements \ArrayAccess, \Countable
{
    public function offsetExists($offset)
    {
        return isset($data[$offset]);
    }

    public function count()
    {
        return count($this->data);
    }

    public function &offsetGet($offset)
    {
        if ($this->isExpired()) {
            $this->data = array();
            $this->setExpiration($this->expirationTime);
        }

        return $this->data[$offset];
    }

    public function __set($name, $value)
    {
        return $this->offsetSet($name, $value);
    }

    public function __get($name)
    {
        return $this->offsetGet($name);
    }

    public function offsetSet($offset, $value)
    {
        if (is_null($offset)) {
            $this->data[] = $value;
            return;
        }
        $this->data[$offset] = $value;

    }

    public function offsetUnset($offset)
    {
        unset($this->data[$offset]);
    }

    /**
     * Sets session namespace's expiration time
     *
     * @param int $expire seconds to expire namespace
     */
    public function setExpiration($expire = 0)
    {
        $this->expirationTime = $expire;
        $this->expireAt = time() + $this->expirationTime;
    }

    /**
     * Checks if session expired
     * @return bool
     */
    public function isExpired()
    {
        return $this->expireAt && time() >= $this->expireAt;
    }

    public function __sleep()
    {
        return array('data', 'expirationTime', 'expireAt');
    }

    public function __wakeup()
    {
        if ($this->isExpired()) {
            $this->data = array();
        }
    }

    protected $data = array();
    protected $expireAt = 0;
    protected $expirationTime = 0;
}