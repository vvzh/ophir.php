<?php
/**
 * Created by PhpStorm.
 * User: thephpjo
 * Date: 14.05.14
 * Time: 20:10
 */
namespace lovasoa;
include("src/Ophir.php");
date_default_timezone_set("Europe/Berlin");


class OphirTest extends \PHPUnit\Framework\TestCase{

	public function run(\PHPUnit\Framework\TestResult $result = NULL): \PHPUnit\Framework\TestResult {
		if ($result === NULL) {
			$result = $this->createResult();
		}
		$configurations = array(
			array(
				Ophir::HEADINGS   => Ophir::ALL,
				Ophir::LISTS      => Ophir::ALL,
				Ophir::TABLE      => Ophir::ALL,
				Ophir::FOOTNOTE   => Ophir::ALL,
				Ophir::LINK       => Ophir::ALL,
				Ophir::IMAGE      => Ophir::ALL,
				Ophir::ANNOTATION => Ophir::ALL,
				Ophir::TOC        => Ophir::ALL,
			),
		);
		foreach ($configurations as $configuration) {
			$this->currentConfiguration = $configuration;
			$this->ophir = new Ophir();
			foreach ($configuration as $option => $value) {
				$this->ophir->setConfiguration($option, $value);
			}
			$this->html = $this->ophir->odt2html(__DIR__."/test.odt");
			// ignore line breaks in tests
			$this->html = str_replace(array("\r", "\n"), "", $this->html);
			$result->run($this);
		}
		return $result;
	}

	public function testSimpleText(){
		$this->assertStringContainsString("<p>This is a simple text Paragraph</p>",$this->html, "testing simple Text");
	}

	public function testTableOfContents(){
		$this->assertStringContainsString("Table of Contents",$this->html);
	}

	public function testFormattedText(){
		$this->assertStringContainsString("This is a <strong>bold text</strong>",	$this->html, "testing bold Text");
		$this->assertStringContainsString("This is a <em>italic text</em>",			$this->html, "testing italic Text");
		$this->assertStringContainsString("This is a <u>underlined text</u>",		$this->html, "testing underlined Text");

		$this->assertStringContainsString("This is a <em><strong>bold italic text</strong></em>",	$this->html, "testing bold italic Text");
		$this->assertStringContainsString("This is a <strong><u>bold underlined text</u></strong>",	$this->html, "testing bold underlined Text");
		$this->assertStringContainsString("This is a <em><u>italic underlined text</u></em>",		$this->html, "testing italic underlined Text");
	}

	public function testOrderedLists(){
		$toTest = "	<ol>
						<li><p>Ordered List</p></li>
						<li><p>wow, so ordered </p></li>
						<li><p>such number</p></li>
					</ol>";
		$toTest = str_replace(array("\n","\r","\t"), "", $toTest);
		$this->assertStringContainsString($toTest,	$this->html, "testing ordered Lists");
	}

	public function testUnorderedLists(){
		$toTest = "	<ul>
						<li><p>unordered List</p></li>
						<li><p>wow, so unordered</p></li>
						<li><p>much messy</p></li>
					</ul>";
		$toTest = str_replace(array("\n","\r","\t"), "", $toTest);
		$this->assertStringContainsString($toTest,	$this->html, "testing ordered Lists");
	}

	public function testImage(){
		$this->assertStringContainsString(base64_encode(file_get_contents(__DIR__."/image.jpg")),$this->html);
	}

	public function testLink(){
		$this->assertStringContainsString('This is a <a href="https://github.com/lovasoa/ophir.php">link</a>',$this->html);
	}

	public function testAnnotation(){
		$this->assertStringContainsString('This is a annotation',$this->html);
	}

	public function testFootnote(){
		$this->assertStringContainsString('This is a footnote', $this->html);
	}

	public function testHeadings(){
		$this->assertStringContainsString("<h1>This is a h1</h1>",$this->html,"testing h1");
		$this->assertStringContainsString("<h2>This is a h2</h2>",$this->html,"testing h2");
		$this->assertStringContainsString("<h3>This is a h3</h3>",$this->html,"testing h3");
	}

	public function testTables(){
		$toTest = "	<table cellspacing=0 cellpadding=0 border=1>
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
		$toTest = str_replace(array("\n","\r","\t"), "", $toTest);

		$this->assertStringContainsString($toTest,$this->html,"testing tables");
	}

	public function testsetConfiguration(){
		$this->ophir->setConfiguration(Ophir::HEADINGS,		Ophir::NONE);
		$this->ophir->setConfiguration(Ophir::LISTS,		Ophir::NONE);
		$this->ophir->setConfiguration(Ophir::TABLE,		Ophir::NONE);
		$this->ophir->setConfiguration(Ophir::FOOTNOTE,		Ophir::NONE);
		$this->ophir->setConfiguration(Ophir::LINK,			Ophir::NONE);
		$this->ophir->setConfiguration(Ophir::IMAGE,		Ophir::NONE);
		$this->ophir->setConfiguration(Ophir::ANNOTATION, 	Ophir::NONE);
		$this->ophir->setConfiguration(Ophir::TOC,			Ophir::NONE);

		foreach($this->ophir->getConfiguration() as $name=>$value){
			$this->assertEquals(Ophir::NONE,$value);
		}

		$this->ophir->setConfiguration(Ophir::HEADINGS,		Ophir::ALL);
		$this->ophir->setConfiguration(Ophir::LISTS,		Ophir::ALL);
		$this->ophir->setConfiguration(Ophir::TABLE,		Ophir::ALL);
		$this->ophir->setConfiguration(Ophir::FOOTNOTE,		Ophir::ALL);
		$this->ophir->setConfiguration(Ophir::LINK,			Ophir::ALL);
		$this->ophir->setConfiguration(Ophir::IMAGE,		Ophir::ALL);
		$this->ophir->setConfiguration(Ophir::ANNOTATION, 	Ophir::ALL);
		$this->ophir->setConfiguration(Ophir::TOC,			Ophir::ALL);

		foreach($this->ophir->getConfiguration() as $name=>$value){
			$this->assertEquals(Ophir::ALL,$value);
		}
	}

}