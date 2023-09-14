<?xml version="1.0" encoding="UTF-8"?>
<!--

-->
<xsl:transform version="1.0"
  xmlns:xsl="http://www.w3.org/1999/XSL/Transform"
  xmlns="http://www.tei-c.org/ns/1.0"
  xmlns:tei="http://www.tei-c.org/ns/1.0"
  exclude-result-prefixes="tei"
  
  xmlns:exslt="http://exslt.org/common"
  xmlns:php="http://php.net/xsl"
  extension-element-prefixes="exslt php"
  >
  <xsl:output encoding="UTF-8" method="xml" indent="no"/>
  <xsl:param name="id"/>
  <xsl:param name="zotero_tei_file"/>
  <xsl:param name="zotero_html_file"/>
  <xsl:variable name="lf" select="'&#10;'"/>
  <xsl:variable name="zotero_tei" select="document($zotero_tei_file)"/>
  <xsl:variable name="zotero_html" select="document($zotero_html_file)"/>
  <xsl:variable name="title" select="/tei:TEI/tei:text/tei:body/tei:head"/>
  <xsl:template match="node()|@*">
    <xsl:copy>
      <xsl:apply-templates select="node()|@*"/>
    </xsl:copy>
  </xsl:template>
  <xsl:template match="/*">
    <xsl:copy>
      <xsl:copy-of select="@*"/>
      <xsl:attribute name="xml:id">
        <xsl:value-of select="$id"/>
      </xsl:attribute>
      <xsl:apply-templates/>
    </xsl:copy>
  </xsl:template>
  <xsl:template match="processing-instruction()">
    <xsl:copy/>
    <xsl:value-of select="$lf"/>
  </xsl:template>
  
  <xsl:template match="tei:teiHeader">
    <xsl:copy>
      <xsl:apply-templates select="@*"/>
      <xsl:apply-templates select="node()[not(self::tei:revisionDesc)]"/>
      <xenoData type="CSL"/>
      <xsl:text>
  </xsl:text>
      <xsl:apply-templates select="tei:revisionDesc"/>
    </xsl:copy>
  </xsl:template>
  <xsl:template match="/tei:TEI/tei:teiHeader/tei:fileDesc/tei:titleStmt/tei:title[tei:error or . = '']">
    <title>
      <xsl:apply-templates select="$title/node()"/>
    </title>
  </xsl:template>
  <!-- Regenerate the xenoData for CSL -->
  <xsl:template match="tei:xenoData[@type = 'CSL' or @type = 'APA7']"/>
  <!-- Notes for Piaget are used by editors -->
  <xsl:template match="tei:biblStruct/tei:note[@corresp]"/>
  <xsl:template match="tei:fileDesc/tei:sourceDesc">
    <xsl:copy>
      <xsl:copy-of select="@*"/>
      <xsl:text>
</xsl:text>
      <xsl:apply-templates select="$zotero_tei/*/tei:biblStruct[*/tei:idno[@type='callNumber'] = $id]"/>
      <xsl:text>
        </xsl:text>
      <bibl type="APA7">
        <xsl:apply-templates select="$zotero_html/*/div[span/@id = $id]/node()" mode="html2tei"/>
      </bibl>
      <xsl:text>
      </xsl:text>
    </xsl:copy>
  </xsl:template>
  
  <xsl:template match="node()|@*" mode="html2tei">
    <xsl:copy>
      <xsl:apply-templates select="node()|@*" mode="html2tei"/>
    </xsl:copy>
  </xsl:template>
  
  <xsl:template match="i" mode="html2tei">
    <hi>
      <xsl:apply-templates mode="html2tei"/>
    </hi>
  </xsl:template>
  <xsl:template match="sup" mode="html2tei">
    <hi rend="sup">
      <xsl:apply-templates mode="html2tei"/>
    </hi>
  </xsl:template>
  <xsl:template match="sub" mode="html2tei">
    <hi rend="sub">
      <xsl:apply-templates mode="html2tei"/>
    </hi>
  </xsl:template>
  <!-- <span style="font-variant:small-caps;"> -->
  <xsl:template match="span[contains(@style, 'small-caps')]" mode="html2tei">
    <hi rend="sc">
      <xsl:apply-templates mode="html2tei"/>
    </hi>
  </xsl:template>
  <xsl:template match="span[@class='nocase']" mode="html2tei">
    <hi>
      <xsl:apply-templates mode="html2tei"/>
    </hi>
  </xsl:template>
  <xsl:template match="span[@id]" mode="html2tei"/>
  <xsl:template match="tei:biblStruct/@corresp"/>
  <!-- Bad if creation date 
  <xsl:template match="tei:creation">
    <xsl:copy>
      <xsl:copy-of select="$zotero_tei/*/tei:biblStruct[*/tei:idno[@type='callNumber'] = $id]/tei:monogr/tei:imprint/tei:date"/>
    </xsl:copy>
  </xsl:template>
  -->
  <xsl:template match="tei:note[@type='debug']"/>
  <xsl:template match="tei:note[@type='CSL']"/>
  <xsl:template match="tei:note[@type='tags']">
    <xsl:if test="tei:term[substring(., 1, 1) != '@']">
      <xsl:copy>
        <xsl:copy-of select="@*"/>
        <xsl:apply-templates select="tei:term[substring(., 1, 1) != '@']"/>
      </xsl:copy>
    </xsl:if>
  </xsl:template>
</xsl:transform>