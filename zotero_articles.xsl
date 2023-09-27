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
  <xsl:param name="zotero_html_file"/>
  <xsl:variable name="zotero_html" select="document($zotero_html_file)"/>
  <xsl:variable name="lf" select="'&#10;'"/>
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
  

  <xsl:template match="tei:div[@type='article']/tei:head/tei:note[1]">
    <xsl:variable name="id" select="ancestor::tei:div[@type='article'][1]/@xml:id"/>
    <xsl:copy>
      <xsl:copy-of select="@*"/>
      <xsl:choose>
        <xsl:when test="not($zotero_html/*/div[span/@id = $id])">
          <xsl:message>404 id=<xsl:value-of select="$id"/></xsl:message>
          <xsl:apply-templates/>
        </xsl:when>
        <xsl:otherwise>
          <xsl:apply-templates select="$zotero_html/*/div[span/@id = $id]/node()" mode="html2tei"/>
        </xsl:otherwise>
      </xsl:choose>
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

</xsl:transform>