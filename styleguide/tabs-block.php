<?php
$the_content = <<<CONTENT
<!-- wp:acf/rh-tabs {"id":"block_62cde15924d76","name":"acf/rh-tabs","data":{"field_tab_block_label":"First Tab"},"align":"","mode":"preview"} -->
<!-- wp:paragraph -->
<p>This is the content for the first tab.</p>
<!-- /wp:paragraph -->
<!-- /wp:acf/rh-tabs -->

<!-- wp:acf/rh-tabs {"id":"block_62cde1ab24d77","name":"acf/rh-tabs","data":{"field_tab_block_label":"Second Tab"},"align":"","mode":"preview"} -->
<!-- wp:paragraph -->
<p>This is the content for the second tab.</p>
<!-- /wp:paragraph -->
<!-- /wp:acf/rh-tabs -->

<!-- wp:acf/rh-tabs {"id":"block_62cde1ce24d78","name":"acf/rh-tabs","data":{"field_tab_block_label":"🏆 Emoji Tab"},"align":"","mode":"preview"} -->
<!-- wp:paragraph -->
<p>This is for using an emoji 🏆</p>
<!-- /wp:paragraph -->
<!-- /wp:acf/rh-tabs -->
CONTENT;

$long_tabs = <<<CONTENT
<!-- wp:acf/rh-tabs {"id":"block_62ceedcfba965","name":"acf/rh-tabs","data":{"field_tab_block_label":"Lorem ipsum dolor sit amet, consectetur adipiscing elit, sed do eiusmod tempor incididunt ut labore et dolore magna aliqua. Aliquet nibh praesent tristique magna sit amet purus"},"align":"","mode":"preview"} -->
<!-- wp:paragraph -->
<p>🅰️ really long content goes here</p>
<!-- /wp:paragraph -->
<!-- /wp:acf/rh-tabs -->

<!-- wp:acf/rh-tabs {"id":"block_62ceeeedba966","name":"acf/rh-tabs","data":{"field_tab_block_label":"Nisl nunc mi ipsum faucibus vitae aliquet nec ullamcorper sit. In est ante in nibh. Nisl tincidunt eget nullam non nisi est sit amet facilisis. Id diam maecenas ultricies mi eget mauris pharetra et ultrices. Interdum varius sit amet mattis. Interdum varius sit amet mattis vulputate enim nulla aliquet. Enim sed faucibus turpis in eu mi bibendum neque."},"align":"","mode":"preview"} -->
<!-- wp:paragraph -->
<p>🅱️ just filler text to show something is different</p>
<!-- /wp:paragraph -->
<!-- /wp:acf/rh-tabs -->
CONTENT;

$args = array(
	'block_name'           => 'rh-tabs',
	'the_title'            => 'Tabs Block',
	'the_description'      => 'Use the <code>RH Tabs</code> block to construct tab content. Tabs can be created anywhere within a post by stringing together tab blocks.',
	'examples'             => array(
		'basic'     => apply_filters( 'the_content', $the_content ),
		'long_tabs' => apply_filters( 'the_content', $long_tabs ),
	),
	'block_directory_name' => 'tabs',
);
get_template_part( 'styleguide', 'block', $args );
