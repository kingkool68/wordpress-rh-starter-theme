<?php
$individual_html_elements = array(
	'a'      => '<a href="#">A link to another page</a>',
	'p'      => '<p>A paragraph of text.</p>',
	'strong' => '<strong>Indicates that its contents have strong importance, seriousness, or urgency</strong>',
	'b'      => '<b>Decorative bold style with no semantic meaning</b>',
	'em'     => '<em>Text that has stress emphasis</em>',
	'i'      => '<i>Decorative italic style with no semantic meaning</i>',
	's'      => '<s>To represent things that are no longer relevant or no longer accurate</s>',
	'del'    => '<del>Represents a range of text that has been deleted from a document</del>',
	'ins'    => '<ins>Represents a range of text that has been added to a document</ins>',
	'u'      => '<u>Represents a span of inline text which should be rendered in a way that indicates that it has a non-textual annotation (usually underline)</u>',
	'abbr'   => '<abbr title="The optional title attribute can provide an expansion or description for the abbreviation">Represents an abbreviation or acronym</abbr>',
	'bdo'    => '<bdo dir="rtl">Overrides the current directionality of text, so that the text within is rendered in a different direction</bdo>',
	'cite'   => '<cite>is used to describe a reference to a cited creative work, and must include the title of that work</cite>',
	'code'   => '<code>Displays its contents styled in a fashion intended to indicate that the text is a short fragment of computer code</code>',
	'dfn'    => '<dfn>Used to indicate the term being defined within the context of a definition phrase or sentence</dfn>',
	'kbd'    => '<kbd>Represents a span of inline text denoting textual user input from a keyboard, voice input, or any other text entry device</kbd>',
	'mark'   => '<mark>Marked or highlighted text for reference or notation purposes</mark>',
	'q'      => '<q>Indicates that the enclosed text is a short inline quotation</q>',
	'small'  => '<small>Represents side-comments and small print, like copyright and legal text, independent of its styled presentation</small>',
	'sub'    => '<sub>Specifies inline text which should be displayed as subscript for solely typographical reasons</sub>',
	'sup'    => '<sup>Specifies inline text which is to be displayed as superscript for solely typographical reasons</sup>',
	'var'    => '<var>Represents the name of a variable in a mathematical expression or a programming context</var>',
);
$context                  = array(
	'individual_html_elements' => $individual_html_elements,
);
Sprig::out( 'styleguide-common-html.twig', $context );
