<?php

namespace Peak\Common\Spl;

use DirectoryIterator;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;

/**
 * Retrieve directory sizes and number of files
 */
class Dirinfo
{
    /**
     * Size
     * @var integer
     */
    protected $size = 0;

    /**
     * Number of files
     * @var integer
     */
    protected $nbfiles = 0;

    /**
     * Gather information about directory
     *
     * @param string $path
     */
    public function __construct($path, $recursive = true)
    {
        if ($recursive) {
            $it = new RecursiveDirectoryIterator($path);

            foreach (new RecursiveIteratorIterator($it) as $f => $c) {
                if ($c->isDir() || $c->isDot()) {
                    continue;
                }
                $size = $c->getSize();
                $this->size += $size;
                ++$this->nbfiles;
            }
        } else {
            foreach (new DirectoryIterator($path) as $f) {
                if ($f->isDot()) {
                    continue;
                }
                $size = $f->getSize();
                $this->size += $size;
                ++$this->nbfiles;
            }
        }
    }

    /**
     * Return directory size
     *
     * @param  bool $format
     * @return string|integer
     */
    public function getSize($format = false)
    {
        if (!$format) {
            return $this->size;
        }
    
        $unit = ['b','kb','mb','gb','tb','pb'];
        return @round($this->size/pow(1024, ($i=floor(log($this->size, 1024)))), 2).' '.$unit[$i];
    }

    /**
     * Return number of files of directory
     *
     * @return integer
     */
    public function getNbfiles()
    {
        return $this->nbfiles;
    }
}
