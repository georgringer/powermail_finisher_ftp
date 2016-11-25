<?php

namespace GeorgRinger\PowermailFinisherFtp\Finisher;

use DOMDocument;
use In2code\Powermail\Domain\Model\Answer;
use In2code\Powermail\Domain\Model\Mail;
use In2code\Powermail\Finisher\AbstractFinisher;
use RuntimeException;
use TYPO3\CMS\Core\Log\Logger;
use TYPO3\CMS\Core\Log\LogManager;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Frontend\ContentObject\ContentObjectRenderer;

class FtpFinisher extends AbstractFinisher
{
    /** @var $logger Logger */
    protected $logger;

    public function __construct(Mail $mail, array $configuration, array $settings, $formSubmitted, $actionMethodName, ContentObjectRenderer $contentObject)
    {
        $this->logger = GeneralUtility::makeInstance(LogManager::class)->getLogger(__CLASS__);

        parent::__construct($mail, $configuration, $settings, $formSubmitted, $actionMethodName, $contentObject);
    }

    /**
     * Finisher which does all the magic
     */
    public function ftpFinisher()
    {
        if (!isset($this->configuration['fieldMapping']) || !is_array($this->configuration['fieldMapping'])) {
            return;
        }

        $filePath = $this->createXml();

        $this->transferXml($filePath, $this->configuration['ftp']);
    }

    /**
     * Upload XML via FTP
     *
     * @param string $localFile
     * @param array $configuration
     */
    protected function transferXml($localFile, array $configuration = null)
    {
        $fileInfo = pathinfo($localFile);
        $basename = $fileInfo['basename'];
        $remoteFile = ltrim($configuration['path'], '/') . '/' . $basename;

        try {
            $connection = ftp_connect($configuration['host']);
            if (!$connection) {
                throw new RuntimeException(sprintf('Connection to host "%s" failed', $configuration['host']));
            }
            $login = ftp_login($connection, $configuration['user'], $configuration['password']);
            if (!$login) {
                throw new RuntimeException(sprintf('Login to host "%s" with user "%s" failed', $configuration['host'], $configuration['user']));
            }
            ftp_pasv($connection, true);
            $success = ftp_put($connection, $remoteFile, $localFile, FTP_ASCII);
            if (!$success) {
                throw new RuntimeException(sprintf('Fle upload "%s" with user "%s" failed', $configuration['host'], $configuration['user']));
            }
        } catch (\Exception $e) {
            $this->logger->warning($e->getMessage());
        }
    }

    /**
     * Generates xml file in typo3temp based on fieldmapping and content
     *
     * @return string
     */
    protected function createXml()
    {
        $xmlDocument = new DOMDocument('1.0', 'utf-8');
        $xmlDocument->formatOutput = true;

        $body = $xmlDocument->createElement('data');

        $fields = $this->getMail()->getAnswersByFieldMarker();
        foreach ($this->configuration['fieldMapping'] as $nameInXml => $nameInForm) {
            $value = $this->getFieldValue($fields, $nameInForm);
            if (is_null($value)) {
                continue;
            }

            $item = $xmlDocument->createElement('field');
            $item->appendChild($xmlDocument->createTextNode($value));
            $item->setAttribute('id', $nameInXml);
            $body->appendChild($item);
        }

        $xmlDocument->appendChild($body);
        $xml = $xmlDocument->saveXML();

        $path = PATH_site . 'typo3temp/PowermailFtpFinisher/';
        $this->secureTempDir($path);
        GeneralUtility::mkdir_deep($path);

        $fileNamePartials = [];
        $fileNamePartialFields = GeneralUtility::trimExplode(',', $this->configuration['fieldsForPath'], true);
        foreach ($fileNamePartialFields as $name) {
            $value = $this->getFieldValue($fields, $name);
            if ($value) {
                $fileNamePartials[] = $value;
            }
        }
        $fileNamePartials[] = $GLOBALS['EXEC_TIME'];
        $path .= $this->generateFileName($fileNamePartials) . '.xml';

        GeneralUtility::writeFile($path, $xml);

        return $path;
    }

    /**
     * Generate clean file name
     *
     * @param array $parts
     * @return string
     */
    protected function generateFileName(array $parts)
    {
        $name = implode('_', $parts);
        $name = str_replace([' '], [''], $name);
        return $name;
    }

    /**
     * @param array $fields
     * @param string $name
     * @return string
     */
    protected function getFieldValue(array $fields, $name)
    {
        $field = $fields[$name];
        if (!is_null($field)) {
            /** @var Answer $field */
            return $field->getRawValue();
        }
    }

    /**
     * @param string $path
     */
    protected function secureTempDir($path)
    {
        $content = '
# Apache < 2.3
<IfModule !mod_authz_core.c>
	Order allow,deny
	Deny from all
	Satisfy All
</IfModule>

# Apache ≥ 2.3
<IfModule mod_authz_core.c>
	Require all denied
</IfModule>';

        GeneralUtility::writeFile($path . '.htaccess', $content);
    }
}
