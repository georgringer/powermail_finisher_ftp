# TYPO3 extension `powermail_finisher_ftp`

This extension acts as finisher for the extension `powermail` and does 2 things:

- Create a XML file with the data of the form
- Upload the XML by using FTP

## Requirements

- TYPO3 7.6 LTS
- powermail 3.8.0
- GPL2

## Usage & Configuration

After installation, you just need to configure the extension properly:

    plugin.tx_powermail.settings.setup {
        finishers.919 {
        class = GeorgRinger\PowermailFinisherFtp\Finisher\FtpFinisher
        
            config {
                
                # name is how it should be named in the XML
                # lastname is the name of the field in powermail
                fieldMapping {
                    name = lastname
                    surname = firstname
                    postalcode = zip
                }
                
                # The file name will be built upon the values of those fields
                # including a timestamp at the end
                fieldsForPath = lastname,firstname
                
                # FTP information
                ftp {
                    host = your.server.tld
                    user = johndoe
                    password = mypassword
                    path = /html/uploads/
                }
            }
        }
    }
    
The produced XML will be persisted in `typo3temp/PowermailFtpFinisher` and its content looks like this:

    <?xml version="1.0" encoding="utf-8"?>
    <data>
      <field id="name">Doe</field>
      <field id="surname">John</field>
      <field id="postalcode">4020</field>
    </data>

