ALTER TABLE  `exf_actest_test_step` ADD  `template_alias` VARCHAR( 128 ) NOT NULL ;
UPDATE `exf_actest_test_step` SET template_alias = 'exface.JEasyUiTemplate';