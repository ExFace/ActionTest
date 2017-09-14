ALTER TABLE exf_actest_test_case ADD COLUMN start_page_alias VARCHAR(245) NOT NULL AFTER start_page_id;
UPDATE exf_actest_test_case eatc LEFT JOIN modx_site_content msc ON eatc.start_page_id = msc.id SET eatc.start_page_alias = msc.alias;
ALTER TABLE exf_actest_test_case DROP start_page_id;

ALTER TABLE exf_actest_test_step ADD COLUMN page_alias VARCHAR(245) NOT NULL AFTER page_id;
UPDATE exf_actest_test_step eats LEFT JOIN modx_site_content msc ON eats.page_id = msc.id SET eats.page_alias = msc.alias;
ALTER TABLE exf_actest_test_step DROP page_id;
