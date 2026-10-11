<?xml version="1.0" encoding="UTF-8"?>
<xsl:stylesheet
    version="1.0"
    xmlns:xsl="http://www.w3.org/1999/XSL/Transform"
    xmlns:sitemap="http://www.sitemaps.org/schemas/sitemap/0.9"
    xmlns:xhtml="http://www.w3.org/1999/xhtml"
    exclude-result-prefixes="sitemap xhtml"
>
    <xsl:output method="html" encoding="UTF-8" indent="yes" />

    <xsl:template match="/">
        <html lang="en">
            <head>
                <meta charset="UTF-8" />
                <meta name="viewport" content="width=device-width, initial-scale=1" />
                <meta name="robots" content="noindex, follow" />
                <title>XML Sitemap</title>
                <style><![CDATA[
                    * {
                        box-sizing: border-box;
                    }

                    body {
                        margin: 0;
                        overflow-x: hidden;
                        color: #1f2933;
                        background: #f3f4f6;
                        font-family: system-ui, -apple-system, "Segoe UI", sans-serif;
                        font-size: 15px;
                        line-height: 1.5;
                    }

                    .wrap {
                        width: 100%;
                        max-width: 100%;
                        padding: 32px 24px 48px;
                    }

                    header {
                        margin-bottom: 20px;
                    }

                    h1 {
                        margin: 0 0 4px;
                        font-size: 1.5rem;
                        font-weight: 650;
                    }

                    .count {
                        margin: 0;
                        color: #6b7280;
                    }

                    .table-wrap {
                        max-width: 100%;
                        overflow-x: auto;
                        border: 1px solid #e5e7eb;
                        border-radius: 8px;
                        background: #fff;
                        -webkit-overflow-scrolling: touch;
                    }

                    table {
                        width: 100%;
                        border-collapse: collapse;
                    }

                    th,
                    td {
                        padding: 12px 16px;
                        text-align: left;
                        vertical-align: top;
                    }

                    th {
                        border-bottom: 1px solid #e5e7eb;
                        color: #6b7280;
                        background: #f9fafb;
                        font-size: 12px;
                        font-weight: 650;
                        letter-spacing: .04em;
                        text-transform: uppercase;
                        white-space: nowrap;
                    }

                    td {
                        border-bottom: 1px solid #f3f4f6;
                    }

                    tr:last-child td {
                        border-bottom: 0;
                    }

                    .col-url {
                        min-width: 28rem;
                        width: 50%;
                    }

                    .col-date,
                    .col-frequency,
                    .col-priority {
                        white-space: nowrap;
                    }

                    a {
                        color: #1d4ed8;
                        text-decoration: none;
                    }

                    a:hover {
                        text-decoration: underline;
                    }

                    a:focus-visible {
                        outline: 2px solid #1d4ed8;
                        outline-offset: 2px;
                    }

                    .url {
                        overflow-wrap: anywhere;
                    }

                    .muted {
                        color: #9ca3af;
                    }

                    .languages {
                        display: flex;
                        flex-wrap: wrap;
                        gap: 8px;
                    }

                    .empty {
                        padding: 24px 16px;
                        color: #6b7280;
                    }
                ]]></style>
            </head>
            <body>
                <div class="wrap">
                    <header>
                        <h1>XML Sitemap</h1>
                        <p class="count">
                            <xsl:value-of select="count(sitemap:urlset/sitemap:url)" /> URLs
                        </p>
                    </header>

                    <xsl:choose>
                        <xsl:when test="count(sitemap:urlset/sitemap:url) &gt; 0">
                            <div class="table-wrap">
                                <table>
                                    <thead>
                                        <tr>
                                            <th class="col-url">URL</th>
                                            <th class="col-date">Last modified</th>
                                            <th class="col-frequency">Frequency</th>
                                            <th class="col-priority">Priority</th>
                                            <th>Languages</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <xsl:for-each select="sitemap:urlset/sitemap:url">
                                            <tr>
                                                <td class="col-url">
                                                    <a class="url" href="{sitemap:loc}">
                                                        <xsl:value-of select="sitemap:loc" />
                                                    </a>
                                                </td>
                                                <td>
                                                    <xsl:choose>
                                                        <xsl:when test="sitemap:lastmod">
                                                            <time datetime="{sitemap:lastmod}">
                                                                <xsl:value-of select="substring(sitemap:lastmod, 1, 10)" />
                                                            </time>
                                                        </xsl:when>
                                                        <xsl:otherwise>
                                                            <span class="muted">—</span>
                                                        </xsl:otherwise>
                                                    </xsl:choose>
                                                </td>
                                                <td>
                                                    <xsl:choose>
                                                        <xsl:when test="sitemap:changefreq">
                                                            <xsl:value-of select="sitemap:changefreq" />
                                                        </xsl:when>
                                                        <xsl:otherwise>
                                                            <span class="muted">—</span>
                                                        </xsl:otherwise>
                                                    </xsl:choose>
                                                </td>
                                                <td>
                                                    <xsl:choose>
                                                        <xsl:when test="sitemap:priority">
                                                            <xsl:value-of select="sitemap:priority" />
                                                        </xsl:when>
                                                        <xsl:otherwise>
                                                            <span class="muted">—</span>
                                                        </xsl:otherwise>
                                                    </xsl:choose>
                                                </td>
                                                <td>
                                                    <xsl:choose>
                                                        <xsl:when test="xhtml:link">
                                                            <div class="languages">
                                                                <xsl:for-each select="xhtml:link">
                                                                    <a href="{@href}" title="Open {@hreflang} version">
                                                                        <xsl:value-of select="@hreflang" />
                                                                    </a>
                                                                </xsl:for-each>
                                                            </div>
                                                        </xsl:when>
                                                        <xsl:otherwise>
                                                            <span class="muted">—</span>
                                                        </xsl:otherwise>
                                                    </xsl:choose>
                                                </td>
                                            </tr>
                                        </xsl:for-each>
                                    </tbody>
                                </table>
                            </div>
                        </xsl:when>
                        <xsl:otherwise>
                            <p class="empty">This sitemap is empty.</p>
                        </xsl:otherwise>
                    </xsl:choose>
                </div>
            </body>
        </html>
    </xsl:template>
</xsl:stylesheet>