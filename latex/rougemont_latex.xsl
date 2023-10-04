<?xml version="1.0" encoding="utf-8"?>
<xsl:transform version="1.0"
  xmlns:xsl="http://www.w3.org/1999/XSL/Transform"
  xmlns:tei="http://www.tei-c.org/ns/1.0"
  exclude-result-prefixes="tei"
>
  <xsl:import href="../vendor/oeuvres/xsl/tei_latex/tei_latex.xsl"/>

  

  <xsl:template match="/tei:teiCorpus//tei:TEI">
    <xsl:text>&#10;\</xsl:text>
    <xsl:choose>
      <xsl:when test="false()"/>
      <xsl:otherwise>section</xsl:otherwise>
    </xsl:choose>
    <!--
    <xsl:text>[</xsl:text>
    <xsl:variable name="raw">
      <xsl:apply-templates select="tei:teiHeader/tei:fileDesc/tei:titleStmt/tei:title[1]" mode="title"/>
    </xsl:variable>
    <xsl:value-of select="normalize-space($raw)"/>
    <xsl:text>]</xsl:text>
    -->
    <xsl:text>{</xsl:text>
    <xsl:apply-templates select="tei:teiHeader/tei:fileDesc/tei:titleStmt/tei:title[1]"/>
    <xsl:text>}</xsl:text>
    <xsl:call-template name="tei:makeHyperTarget">
      <xsl:with-param name="id" select="@xml:id"/>
    </xsl:call-template>
    <xsl:text>&#10;\observation{</xsl:text>
    <xsl:text>&#10;</xsl:text>
    <xsl:text>{\noindent}Réf originale : </xsl:text>
    <xsl:apply-templates select="tei:teiHeader/tei:fileDesc/tei:sourceDesc/tei:bibl[1]/node()"/>
    <xsl:text>\par}&#10;</xsl:text>
    <xsl:apply-templates select="tei:text/*[not(self::tei:head)]"/>
  </xsl:template>

  <xsl:template name="level">
    <xsl:variable name="chapter" select="ancestor-or-self::*[key('CHAPTERS',generate-id(.))][1]"/>
    <xsl:choose>
      <xsl:when test="/tei:teiCorpus">
        <xsl:value-of select="count(ancestor-or-self::tei:div) + count(ancestor-or-self::tei:TEI)"/>
      </xsl:when>
      <xsl:when test="$chapter">
        <xsl:value-of select="count(ancestor-or-self::tei:div[ancestor::*[count(.|$chapter) = 1]])"/>
      </xsl:when>
      <xsl:when test="ancestor-or-self::tei:div[1]/descendant::*[key('CHAPTERS',generate-id(.))]">-1</xsl:when>
      <xsl:otherwise>
        <xsl:value-of select="count(ancestor-or-self::tei:div)"/>
      </xsl:otherwise>
    </xsl:choose>
  </xsl:template>

</xsl:transform>
