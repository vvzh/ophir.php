<?php
/**
 * Created by PhpStorm.
 * User: thephpjo
 * Date: 14.05.14
 * Time: 20:10
 */
namespace lovasoa;
require_once(__DIR__ . "/../src/Ophir.php");

class OphirTest extends \PHPUnit\Framework\TestCase{

	protected $configurationsData;

	protected function addOptionCombinations($currentCombinations, $option, $values){
		$result = array();
		if ($currentCombinations) {
			foreach ($currentCombinations as $combination) {
				foreach ($values as $value) {
					$result[] = $combination + array($option => $value);
				}
			}
		} else {
			foreach ($values as $value) {
				$result[] = array($option => $value);
			}
		}
		return $result;
	}

	protected function getOutput($configuration) {
		$ophir = new Ophir();
		foreach ($configuration as $option => $value) {
			$ophir->setConfiguration($option, $value);
		}
		$output = $ophir->odt2html(__DIR__."/test.odt");
		unset($ophir);
		// ignore line breaks in Ophir output
		return str_replace(array("\r", "\n"), "", $output);
	}

	protected function formatMessage($message, $configuration) {
		$valueNameByValue = array(0 => 'None', 1 => 'Simple', 2 => 'All');
		$parts = array();
		foreach ($configuration as $option => $value) {
			$parts[] = "$option=$valueNameByValue[$value]";
		}
		return "$message (" . implode(', ', $parts) . ")";
	}

	protected function doThreeWayConfigBasedTest($configuration, $option, $name, $sample){
		$output = $this->getOutput($configuration);
		$all = str_replace(array("\n","\r","\t"), "", $sample);
		$simple = strip_tags($all, '<p>');

		$configValue = $configuration[Ophir::LISTS];
		if ($configValue === Ophir::ALL) {
			$this->assertStringContainsString($all, $output,
				$this->formatMessage("$name (all)", $configuration));
		} else if ($configValue === Ophir::SIMPLE) {
			$this->assertStringContainsString($simple, $output,
				$this->formatMessage("$name (simple)", $configuration));
		} else {
			$this->assertStringNotContainsString($all, $output,
				$this->formatMessage("$name (none, part 1)", $configuration));
			$this->assertStringNotContainsString($simple, $output,
				$this->formatMessage("$name (none, part 2)", $configuration));
		}
	}

	protected function doTwoWayConfigBasedTest($configuration, $option, $name, $sample){
		$output = $this->getOutput($configuration);
		$configValue = $configuration[$option];
		if ($configValue === Ophir::ALL) {
			$this->assertStringContainsString($sample, $output,
				$this->formatMessage("$name (enabled)", $configuration));
		} else {
			$this->assertStringNotContainsString($sample, $output,
				$this->formatMessage("$name (disabled)", $configuration));
		}
	}

	public function configurationsDataProvider(){
		if (!$this->configurationsData) {
			$possibleOptions = array(Ophir::HEADINGS, Ophir::LISTS, Ophir::TABLE, Ophir::FOOTNOTE, Ophir::LINK, Ophir::IMAGE, Ophir::ANNOTATION, Ophir::TOC);
			$possibleValues = array(Ophir::NONE, Ophir::SIMPLE, Ophir::ALL);
			$configurations = array();
			foreach ($possibleOptions as $option) {
				$configurations = $this->addOptionCombinations($configurations, $option, $possibleValues);
			}
			foreach ($configurations as $configuration) {
				// $output = $this->getOutput($configuration);
				$this->configurationsData[] = array($configuration);
			}
		}
		return $this->configurationsData;
	}

	/**
	* @dataProvider configurationsDataProvider
	*/
	public function testSimpleText($configuration){
		$output = $this->getOutput($configuration);
		$this->assertStringContainsString("<p>This is a simple text Paragraph</p>", $output,
			$this->formatMessage("Text Paragraph", $configuration));
	}

	/**
	* @dataProvider configurationsDataProvider
	*/
	public function testTableOfContents($configuration){
		$output = $this->getOutput($configuration);
		$configValue = $configuration[Ophir::TOC];
		if ($configValue === Ophir::ALL || $configValue === Ophir::SIMPLE) {
			$this->assertStringContainsString("Table of Contents", $output,
				$this->formatMessage("TOC (enabled)", $configuration));
		} else {
			$this->assertStringNotContainsString("Table of Contents", $output,
				$this->formatMessage("TOC (disabled)", $configuration));
		}
	}

	/**
	* @dataProvider configurationsDataProvider
	*/
	public function testFormattedText($configuration){
		$output = $this->getOutput($configuration);
		$this->assertStringContainsString("This is a <strong>bold text</strong>", $output,
			$this->formatMessage("Bold Text", $configuration));
		$this->assertStringContainsString("This is a <em>italic text</em>", $output,
			$this->formatMessage("Italic Text", $configuration));
		$this->assertStringContainsString("This is a <u>underlined text</u>", $output,
			$this->formatMessage("Underlined Text", $configuration));

		$this->assertStringContainsString("This is a <em><strong>bold italic text</strong></em>", $output,
			$this->formatMessage("Bold Italic Text", $configuration));
		$this->assertStringContainsString("This is a <strong><u>bold underlined text</u></strong>", $output,
			$this->formatMessage("Bold Underlined Text", $configuration));
		$this->assertStringContainsString("This is a <em><u>italic underlined text</u></em>", $output,
			$this->formatMessage("Italic Underlined Text", $configuration));
	}

	/**
	* @dataProvider configurationsDataProvider
	*/
	public function testOrderedLists($configuration){
		$sample = "	<ol>
						<li><p>Ordered List</p></li>
						<li><p>wow, so ordered </p></li>
						<li><p>such number</p></li>
					</ol>";
		$this->doThreeWayConfigBasedTest($configuration, Ophir::LISTS, "Ordered Lists", $sample);
	}

	/**
	* @dataProvider configurationsDataProvider
	*/
	public function testUnorderedLists($configuration){
		$sample = "	<ol>
						<li><p>Ordered List</p></li>
						<li><p>wow, so ordered </p></li>
						<li><p>such number</p></li>
					</ol>";
		$this->doThreeWayConfigBasedTest($configuration, Ophir::LISTS, "Unordered Lists", $sample);
	}

	/**
	* @dataProvider configurationsDataProvider
	*/
	public function testImage($configuration){
		// TODO: test with Ophir::IMAGEFOLDER set to some reasonable value too
		$sample = base64_encode(file_get_contents(__DIR__."/image.jpg"));
		$this->doTwoWayConfigBasedTest($configuration, Ophir::IMAGE, "Image", $sample);
	}

	/**
	* @dataProvider configurationsDataProvider
	*/
	public function testLink($configuration){
		$this->doTwoWayConfigBasedTest($configuration, Ophir::LINK, "Link", 'This is a <a href="https://github.com/lovasoa/ophir.php">link</a>');
	}

	/**
	* @dataProvider configurationsDataProvider
	*/
	public function testAnnotation($configuration){
		$output = $this->getOutput($configuration);
		$this->assertStringContainsString('This is a annotation', $output);
	}

	/**
	* @dataProvider configurationsDataProvider
	*/
	public function testFootnote($configuration){
		$output = $this->getOutput($configuration);
		$this->assertStringContainsString('This is a footnote', $output);
	}

	/**
	* @dataProvider configurationsDataProvider
	*/
	public function testHeadings($configuration){
		$output = $this->getOutput($configuration);
		$configValue = $configuration[Ophir::HEADINGS];
		if ($configValue === Ophir::ALL) {
			$this->assertStringContainsString("<h1>This is a h1</h1>", $output, "testing h1");
			$this->assertStringContainsString("<h2>This is a h2</h2>", $output, "testing h2");
			$this->assertStringContainsString("<h3>This is a h3</h3>", $output, "testing h3");
		} else if ($configValue === Ophir::SIMPLE) {
			$this->assertStringContainsString("This is a h1", $output, "testing h1");
			$this->assertStringContainsString("This is a h2", $output, "testing h2");
			$this->assertStringContainsString("This is a h3", $output, "testing h3");
			$this->assertStringNotContainsString("<h1>This is a h1</h1>",$output, "testing h1");
			$this->assertStringNotContainsString("<h2>This is a h2</h2>",$output, "testing h2");
			$this->assertStringNotContainsString("<h3>This is a h3</h3>",$output, "testing h3");
		} else {
			$this->assertStringNotContainsString("This is a h1", $output, "testing h1");
			$this->assertStringNotContainsString("This is a h2", $output, "testing h2");
			$this->assertStringNotContainsString("This is a h3", $output, "testing h3");
		}
	}

	/**
	* @dataProvider configurationsDataProvider
	*/
	public function testTables($configuration){
		$sample = "	<table cellspacing=0 cellpadding=0 border=1>
						<tr>
							<td><p>So</p></td>
							<td><p>Much</p></td>
							<td><p>Table</p></td>
						</tr>
						<tr>
							<td><p>Going</p></td>
							<td><p>On</p></td>
							<td><p>In</p></td>
						</tr>
						<tr>
							<td><p>This</p></td>
							<td><p>Particular</p>
							</td><td><p>File</p></td>
						</tr>
					</table>";
		$this->doThreeWayConfigBasedTest($configuration, Ophir::TABLE, "Tables", $sample);
	}

	/**
	* @dataProvider configurationsDataProvider
	*/
	public function testsetConfiguration($configuration){
		$ophir = new Ophir();
		foreach ($configuration as $option => $value) {
			$ophir->setConfiguration($option, $value);
		}
		foreach($ophir->getConfiguration() as $option => $value){
			$this->assertEquals($configuration[$option], $value);
		}
	}

}