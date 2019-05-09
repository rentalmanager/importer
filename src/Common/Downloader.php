<?php
namespace RentalManager\Importer\Common;

use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Storage;
use InvalidArgumentException;

/**
 * Created by PhpStorm.
 * User: gorankrgovic
 * Date: 9/8/18
 * Time: 8:49 AM
 */


class Downloader
{


    /**
     * A method protocol from which we are downloading the file
     *
     * @var string
     */
    private $protocol = 'http';


    /**
     * A file which we are downloading
     *
     * @var string
     */
    private $file;


    /**
     * A file extension to be set
     *
     * @var string
     */
    private $extension;

    /**
     * Storage saved extension
     *
     * @var
     */
    private $storage_ext;


    /**
     * An error object which can be returned
     *
     * @var object
     */
    private $error;


    /**
     * A path where we are storing the file
     *
     * @var string
     */
    private $storage_path;


    /**
     * Gets a saved file full path
     *
     * @var string
     */
    private $saved_file;


    /**
     * Gets the feed file size
     *
     * @var int
     */
    private $file_size = false;


    /**
     * What should we do after download
     *
     * @var bool | string
     */
    private $after_download = false;


    /**
     * Download
     *
     * @return $this
     */
    public function download()
    {
        switch( $this->protocol )
        {
            case 'http':
                $this->_http();
                break;

            case 'ftp':
                $this->_ftp();
                break;

            default:
                $this->_http();
                break;
        }

        return $this;
    }



    private function _http()
    {
        if ( !$this->file || !$this->storage_path || !$this->extension )
        {
            throw new InvalidArgumentException('File and or storage path is not set');
        }

        try {

            $whereTo = $this->storage_path . '/' . Config::get('importer.feed_file_name') . '-' . date('Y-m-d_H-i-s') . '.' . $this->extension;

            Storage::put($whereTo, fopen($this->file, 'r'));
            $this->file_size = Storage::size( $whereTo );
            $this->saved_file = storage_path('app/' . $whereTo );

        } catch (\Exception $e) {

            $this->error = $e->getMessage();
        }

        return $this;
    }

    public function _ftp()
    {
        if ( !$this->file || !$this->storage_path || !$this->extension )
        {
            throw new InvalidArgumentException('File and or storage path is not set');
        }

        try {

            $whereTo = $this->storage_path . '/' . Config::get('importer.feed_file_name') . '-' . date('Y-m-d_H-i-s') . '.' . $this->extension;

            $provider = explode('/',$this->storage_path)[1];
            $providerConfig = Config::get('importer.feeds.'.$provider)[0];

            $ftp = Storage::createFtpDriver([
                'host' => $providerConfig['host'],
                'username' => $providerConfig['user'],
                'password' => $providerConfig['pass']
            ]);

            Storage::put($whereTo, $ftp->get($this->file));
            $this->file_size = Storage::size( $whereTo );
            $this->saved_file = storage_path('app/' . $whereTo );

        } catch (\Exception $e) {

            $this->error = $e->getMessage();
        }

        return $this;
    }

    /**
     * Sets and executes the after method
     *
     * @param $after
     * @return $this;
     */
    public function after($after, $do_ext)
    {
        $this->after_download = $after;
        $this->storage_ext = $do_ext;

        if ( !$this->error )
        {
            // proceed
            if ( $this->after_download )
            {
                $method = '_' . $this->after_download;
                $this->$method();
            }
        }

        return $this;
    }


    /**
     * Sets a file extension
     *
     * @param $ext
     * @return $this
     */
    public function extension($ext)
    {
        $this->extension = $ext;
        return $this;
    }

    /**
     * Sets a file
     *
     * @param $file
     * @return $this
     */
    public function file($file)
    {
        $this->file = $file;
        return $this;
    }


    /**
     * Sets a storage
     *
     * @param $path
     * @return $this
     */
    public function storage($path)
    {
        $this->storage_path = $path;
        return $this;
    }


    /**
     * Sets a protocol
     *
     * @param $protocol
     * @return $this
     */
    public function protocol($protocol)
    {
        $this->protocol = $protocol;
        return $this;
    }


    /**
     * Get the info
     *
     * @return object
     */
    public function info()
    {
        return (object) [
            'error' => $this->error,
            'output' => $this->saved_file,
            'size' => $this->file_size
        ];
    }

    // after methods

    /**
     * Gunzip the file
     *
     * @return $this
     */
    private function _gunzip()
    {
        // Raising this value may increase performance
        $buffer_size = 4096; // read 4kb at a time

        // Open our files (in binary mode)
        // file where we need to store
        $outputPath = storage_path('app/' . $this->storage_path . '/' . Config::get('importer.feed_file_name') . '-' . date('Y-m-d_H-i-s') . '.' . $this->storage_ext);
        $file = gzopen($this->saved_file, 'rb');
        $out_file = fopen($outputPath, 'wb');

        // Keep repeating until the end of the input file
        while (!gzeof($file)) {
            // Read buffer-size bytes
            // Both fwrite and gzread and binary-safe
            fwrite($out_file, gzread($file, $buffer_size));
        }

        // Files are done, close files
        fclose($out_file);
        gzclose($file);

        // change the saved file
        $this->saved_file = $outputPath;

        return $this;
    }

}
