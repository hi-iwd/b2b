<?php
namespace IWD\B2B\Helper;

use Magento\Framework\App\Filesystem\DirectoryList;

class Images extends \Magento\Framework\App\Helper\AbstractHelper
{
    /**
     * Maximum size for image in bytes
     * Default value is 1M
     *
     * @var int
     */
    const MAX_FILE_SIZE = 1048576;
    /**
     * Manimum image height in pixels
     *
     * @var int
     */
    const MIN_HEIGHT = 50;
    /**
     * Maximum image height in pixels
     *
     * @var int
     */
    const MAX_HEIGHT = 800;
    /**
     * Manimum image width in pixels
     *
     * @var int
     */
    const MIN_WIDTH = 50;
    /**
     * Maximum image width in pixels
     *
     * @var int
     */
    const MAX_WIDTH = 1024;
    /**
     * Array of image size limitation
     *
     * @var array
     */
    protected $_imageSize   = [
        'minheight'     => self::MIN_HEIGHT,
        'minwidth'      => self::MIN_WIDTH,
        // 'maxheight'     => self::MAX_HEIGHT,
        // 'maxwidth'      => self::MAX_WIDTH,
    ];

    /**
     * @var \Magento\Framework\Filesystem\Directory\WriteInterface
     */
    protected $mediaDirectory;
    /**
     * @var \Magento\Framework\Filesystem
     */
    protected $filesystem;
    /**
     * @var \Magento\Framework\HTTP\Adapter\FileTransferFactory
     */
    protected $httpFactory;

    /**
     * File Uploader factory
     *
     * @var \Magento\MediaStorage\Model\File\UploaderFactory
     */
    protected $_fileUploaderFactory;

    /**
     * File Uploader factory
     *
     * @var \Magento\Framework\Io\File
     */
    protected $_ioFile;

    public function __construct(
            \Magento\Framework\App\Helper\Context $context,
            \Magento\Framework\Filesystem $filesystem,
            \Magento\Framework\HTTP\Adapter\FileTransferFactory $httpFactory,
            \Magento\MediaStorage\Model\File\UploaderFactory $fileUploaderFactory,
            \Magento\Framework\Filesystem\Io\File $ioFile
    ) {
        parent::__construct($context);

        $this->filesystem = $filesystem;
        $this->mediaDirectory = $filesystem->getDirectoryWrite(DirectoryList::MEDIA);
        $this->httpFactory = $httpFactory;
        $this->_fileUploaderFactory = $fileUploaderFactory;
        $this->_ioFile = $ioFile;
    }

    /**
    * Remove image by image filename
    *
    * @param string $imageFile
    * @return bool
    */
    public function removeImage($imageFile)
    {
        $io = $this->_ioFile;
        $io->open(['path' => $this->getBaseDir()]);
        if ($io->fileExists($imageFile)) {
            return $io->rm($imageFile);
        }
        return false;
    }

    /**
     * Upload image and return uploaded image file name or false
     *
     * @throws Mage_Core_Exception
     * @param string $scope the request key for file
     * @return bool|string
     */
    public function uploadImage($scope, $type = '', $validate = false)
    {
        $adapter = $this->httpFactory->create();

        if($validate){
            $adapter->addValidator(new \Zend_Validate_File_ImageSize($this->_imageSize));
            $adapter->addValidator(
                new \Zend_Validate_File_FilesSize(['max' => self::MAX_FILE_SIZE])
            );
        }

        if ($adapter->isUploaded($scope)) {
            // validate image
            if (!$adapter->isValid($scope)) {
                throw new \Exception(__('Uploaded image is not valid.'));
            }

            $uploader = $this->_fileUploaderFactory->create(['fileId' => $scope]);
            $uploader->setAllowedExtensions(['jpg', 'jpeg', 'gif', 'png']);
            $uploader->setAllowRenameFiles(true);
            $uploader->setFilesDispersion(false);
            $uploader->setAllowCreateFolders(true);

            $path = '';
            switch($type){
                case 'company':
                    $path = 'b2b/companies/';
                    break;
                case 'certificate':
                    $path = 'b2b/certificate/';
                    break;
            }

            $result = $uploader->save($this->getBaseDir($path));
            if ($result) {
                $fileName = $uploader->getUploadedFileName();

                return $path.$fileName;
            }
        }
        return false;
    }

    /**
     * Return the base media directory for images
     *
     * @return string
     */
    public function getBaseDir($path = '')
    {
        return $this->filesystem->getDirectoryRead(DirectoryList::MEDIA)->getAbsolutePath($path);
    }
    ////

}
