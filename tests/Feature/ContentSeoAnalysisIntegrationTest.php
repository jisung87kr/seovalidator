<?php

namespace Tests\Feature;

use App\Analyzers\ContentStructureAnalyzer;
use App\Analyzers\KeywordAnalyzer;
use App\Analyzers\ReadabilityAnalyzer;
use App\DTOs\CrawlResult;
use App\Services\ContentSeoAnalyzer;
use App\Utils\TextProcessor;
use Carbon\Carbon;
use Tests\TestCase;

class ContentSeoAnalysisIntegrationTest extends TestCase
{
    private ContentSeoAnalyzer $contentSeoAnalyzer;

    protected function setUp(): void
    {
        parent::setUp();

        // Create real instances for integration testing
        $this->contentSeoAnalyzer = new ContentSeoAnalyzer(
            new TextProcessor(),
            new KeywordAnalyzer(),
            new ReadabilityAnalyzer(),
            new ContentStructureAnalyzer()
        );
    }

    public function test_complete_seo_analysis_workflow(): void
    {
        // Arrange - Create a realistic HTML content
        $htmlContent = $this->createRealisticHtmlContent();
        $crawlResult = CrawlResult::success(
            'https://example.com/seo-guide',
            $htmlContent,
            $this->extractDataFromHtml($htmlContent),
            0.5,
            200
        );

        $targetKeywords = ['seo', 'optimization', 'search engine'];

        // Act
        $results = $this->contentSeoAnalyzer->analyze($crawlResult, $targetKeywords);

        // Assert - Verify all components are working together
        $this->assertIsArray($results);
        $this->assertArrayHasKey('text_processing', $results);
        $this->assertArrayHasKey('keyword_analysis', $results);
        $this->assertArrayHasKey('readability_analysis', $results);
        $this->assertArrayHasKey('structure_analysis', $results);
        $this->assertArrayHasKey('overall_score', $results);
        $this->assertArrayHasKey('suggestions', $results);

        // Verify text processing results
        $textResult = $results['text_processing'];
        $this->assertGreaterThan(100, $textResult->wordCount);
        $this->assertEquals('en', $textResult->detectedLanguage);
        $this->assertGreaterThan(0, $textResult->textDensity);

        // Verify keyword analysis results
        $keywordResult = $results['keyword_analysis'];
        $this->assertNotEmpty($keywordResult->densityMap);
        $this->assertArrayHasKey('seo', $keywordResult->densityMap);
        $this->assertGreaterThan(0, $keywordResult->overallDensity);

        // Verify readability analysis results
        $readabilityResult = $results['readability_analysis'];
        $this->assertGreaterThan(0, $readabilityResult->fleschKincaidScore);
        $this->assertGreaterThan(0, $readabilityResult->totalWords);
        $this->assertIsString($readabilityResult->readingLevel);

        // Verify structure analysis results
        $structureResult = $results['structure_analysis'];
        $this->assertIsArray($structureResult->headingStructure);
        $this->assertIsArray($structureResult->linkAnalysis);
        $this->assertGreaterThan(0, $structureResult->contentLength);

        // Verify overall score
        $this->assertIsInt($results['overall_score']);
        $this->assertGreaterThanOrEqual(0, $results['overall_score']);
        $this->assertLessThanOrEqual(100, $results['overall_score']);

        // Verify suggestions
        $this->assertIsArray($results['suggestions']);
        $this->assertArrayHasKey('priority', $results['suggestions']);
    }

    public function test_analysis_with_poor_content(): void
    {
        // Arrange - Create poor quality content
        $htmlContent = '<html><body><p>bad content bad content bad content bad content bad content</p></body></html>';
        $crawlResult = CrawlResult::success(
            'https://example.com/poor-content',
            $htmlContent,
            [],
            0.5,
            200
        );

        // Act
        $results = $this->contentSeoAnalyzer->analyze($crawlResult, ['bad']);

        // Assert - Should identify issues
        $this->assertLessThan(50, $results['overall_score']); // Poor score for poor content
        $this->assertTrue($results['keyword_analysis']->hasKeywordStuffing); // Should detect stuffing
        
        // Should have suggestions for improvement
        $allSuggestions = array_merge(
            $results['suggestions']['keyword'] ?? [],
            $results['suggestions']['readability'] ?? [],
            $results['suggestions']['structure'] ?? []
        );
        $this->assertNotEmpty($allSuggestions);
    }

    public function test_analysis_with_excellent_content(): void
    {
        // Arrange - Create high quality content
        $htmlContent = $this->createHighQualityHtmlContent();
        $crawlResult = CrawlResult::success(
            'https://example.com/excellent-content',
            $htmlContent,
            $this->extractDataFromHtml($htmlContent),
            0.5,
            200
        );

        // Act
        $results = $this->contentSeoAnalyzer->analyze($crawlResult, ['web development', 'programming']);

        // Assert - Should score well
        $this->assertGreaterThan(70, $results['overall_score']); // Good score for quality content
        $this->assertFalse($results['keyword_analysis']->hasKeywordStuffing); // No keyword stuffing
        $this->assertGreaterThan(60, $results['readability_analysis']->fleschKincaidScore); // Good readability
        
        // Structure should be good
        $headingQuality = $results['structure_analysis']->headingStructure['quality_score'] ?? 0;
        $this->assertGreaterThan(70, $headingQuality);
    }

    public function test_korean_content_analysis(): void
    {
        // Arrange - Create Korean content
        $htmlContent = $this->createKoreanHtmlContent();
        $crawlResult = CrawlResult::success(
            'https://example.com/korean-content',
            $htmlContent,
            [],
            0.5,
            200
        );

        // Act
        $results = $this->contentSeoAnalyzer->analyze($crawlResult, ['웹개발', '프로그래밍']);

        // Assert
        $this->assertEquals('ko', $results['text_processing']->detectedLanguage);
        $this->assertIsArray($results['keyword_analysis']->densityMap);
        $this->assertGreaterThan(0, $results['readability_analysis']->fleschKincaidScore);
        $this->assertIsInt($results['overall_score']);
    }

    public function test_quick_analysis_methods(): void
    {
        // Test quick keyword density analysis
        $content = '<p>SEO optimization guide for better search engine optimization and SEO results.</p>';
        $keywordResult = $this->contentSeoAnalyzer->analyzeKeywordDensity($content, ['seo', 'optimization']);
        
        $this->assertArrayHasKey('seo', $keywordResult->densityMap);
        $this->assertArrayHasKey('optimization', $keywordResult->densityMap);

        // Test quick readability analysis
        $readabilityResult = $this->contentSeoAnalyzer->analyzeReadability($content);
        
        $this->assertGreaterThan(0, $readabilityResult->fleschKincaidScore);
        $this->assertIsString($readabilityResult->readingLevel);

        // Test quick structure analysis
        $crawlResult = CrawlResult::success('https://test.com', $content, [], 0.5, 200);
        $structureResult = $this->contentSeoAnalyzer->analyzeContentStructure($crawlResult);
        
        $this->assertIsArray($structureResult->headingStructure);
        $this->assertGreaterThan(0, $structureResult->contentLength);
    }

    public function test_content_score_calculation(): void
    {
        // Arrange
        $htmlContent = $this->createRealisticHtmlContent();
        $crawlResult = CrawlResult::success('https://test.com', $htmlContent, [], 0.5, 200);

        // Act
        $results = $this->contentSeoAnalyzer->analyze($crawlResult);
        $score = $this->contentSeoAnalyzer->calculateContentScore($results);

        // Assert
        $this->assertEquals($results['overall_score'], $score);
        $this->assertIsInt($score);
        $this->assertGreaterThanOrEqual(0, $score);
        $this->assertLessThanOrEqual(100, $score);
    }

    public function test_analysis_with_empty_content(): void
    {
        // Arrange
        $crawlResult = CrawlResult::failure('https://example.com', 'No content found');

        // Act
        $results = $this->contentSeoAnalyzer->analyze($crawlResult);

        // Assert - Should handle gracefully
        $this->assertEquals(0, $results['overall_score']);
        $this->assertArrayHasKey('suggestions', $results);
        $this->assertArrayHasKey('error', $results['suggestions']);
    }

    public function test_performance_with_large_content(): void
    {
        // Arrange - Create large content
        $largeContent = $this->createLargeHtmlContent();
        $crawlResult = CrawlResult::success('https://example.com/large', $largeContent, [], 0.5, 200);

        // Act
        $startTime = microtime(true);
        $results = $this->contentSeoAnalyzer->analyze($crawlResult);
        $executionTime = microtime(true) - $startTime;

        // Assert - Should complete in reasonable time (less than 5 seconds)
        $this->assertLessThan(5.0, $executionTime);
        $this->assertIsArray($results);
        $this->assertArrayHasKey('overall_score', $results);
    }

    private function createRealisticHtmlContent(): string
    {
        return '
            <!DOCTYPE html>
            <html>
            <head>
                <title>Complete SEO Optimization Guide</title>
                <meta name="description" content="Learn comprehensive SEO optimization techniques for better search engine rankings.">
            </head>
            <body>
                <h1>Complete SEO Optimization Guide</h1>
                
                <p>Search engine optimization (SEO) is crucial for online success. This comprehensive guide covers essential SEO techniques that help improve your website\'s visibility in search results.</p>
                
                <h2>Understanding SEO Fundamentals</h2>
                <p>SEO involves optimizing your website to rank higher in search engine results. Good optimization practices include keyword research, content optimization, and technical improvements.</p>
                
                <h3>Keyword Research and Analysis</h3>
                <p>Effective keyword research forms the foundation of any successful SEO strategy. Understanding what your audience searches for helps create relevant content.</p>
                
                <h2>Content Optimization Strategies</h2>
                <p>Quality content drives organic traffic. Focus on creating valuable, informative content that addresses user intent and provides comprehensive answers.</p>
                
                <h3>On-Page SEO Elements</h3>
                <p>Optimize title tags, meta descriptions, and header structure. These elements help search engines understand your content better.</p>
                
                <p>Remember to include internal links to related content and use descriptive anchor text. External links to authoritative sources also add credibility.</p>
                
                <img src="seo-guide.jpg" alt="SEO optimization strategies and techniques">
                
                <h2>Technical SEO Considerations</h2>
                <p>Technical optimization includes site speed, mobile responsiveness, and proper URL structure. These factors significantly impact search rankings.</p>
                
                <p>Regular content audits help maintain quality standards. Monitor performance metrics and adjust strategies based on data insights.</p>
                
                <a href="/keyword-research" title="Learn about keyword research">Keyword Research Guide</a>
                <a href="/technical-seo" title="Technical SEO best practices">Technical SEO Tips</a>
                <a href="https://example.com/external-seo-guide" title="External SEO resource">External SEO Resource</a>
            </body>
            </html>
        ';
    }

    private function createHighQualityHtmlContent(): string
    {
        return '
            <!DOCTYPE html>
            <html>
            <head>
                <title>Modern Web Development Best Practices</title>
                <meta name="description" content="Discover modern web development practices that enhance user experience and code quality.">
            </head>
            <body>
                <h1>Modern Web Development Best Practices</h1>
                
                <p>Web development evolves rapidly. Staying current with best practices ensures robust, maintainable applications that provide excellent user experiences.</p>
                
                <h2>Frontend Development Excellence</h2>
                <p>Modern frontend development emphasizes component-based architecture. This approach promotes code reusability and maintainability across large projects.</p>
                
                <h3>JavaScript Frameworks and Libraries</h3>
                <p>Popular frameworks like React, Vue, and Angular streamline development workflows. Choose tools that align with project requirements and team expertise.</p>
                
                <h3>CSS Best Practices</h3>
                <p>Semantic markup and organized stylesheets improve code readability. Use CSS methodologies like BEM or utility-first approaches for consistency.</p>
                
                <h2>Backend Architecture Patterns</h2>
                <p>Scalable backend systems require thoughtful architecture. Consider microservices, API design, and database optimization for robust applications.</p>
                
                <h3>Database Design Principles</h3>
                <p>Efficient database design supports application performance. Normalize data structures and implement appropriate indexing strategies.</p>
                
                <p>Security considerations must be integrated throughout development. Validate inputs, encrypt sensitive data, and follow authentication best practices.</p>
                
                <img src="web-dev-stack.jpg" alt="Modern web development technology stack diagram">
                <img src="coding-setup.jpg" alt="Professional programming workspace setup">
                
                <h2>Testing and Quality Assurance</h2>
                <p>Comprehensive testing prevents production issues. Implement unit tests, integration tests, and end-to-end testing for reliable software delivery.</p>
                
                <h3>Continuous Integration and Deployment</h3>
                <p>Automated CI/CD pipelines streamline development workflows. Deploy code changes confidently with proper testing and monitoring systems.</p>
                
                <p>Performance optimization enhances user satisfaction. Monitor application metrics and optimize bottlenecks proactively.</p>
                
                <a href="/frontend-guide" title="Frontend development guide">Frontend Development</a>
                <a href="/backend-architecture" title="Backend architecture patterns">Backend Architecture</a>
                <a href="/testing-strategies" title="Testing methodologies">Testing Strategies</a>
                <a href="https://developer.mozilla.org" title="MDN Web Docs">MDN Web Documentation</a>
            </body>
            </html>
        ';
    }

    private function createKoreanHtmlContent(): string
    {
        return '
            <!DOCTYPE html>
            <html>
            <head>
                <title>웹개발 가이드</title>
                <meta name="description" content="현대적인 웹개발 기술과 프로그래밍 방법론을 학습하세요.">
            </head>
            <body>
                <h1>현대적인 웹개발 가이드</h1>
                
                <p>웹개발은 빠르게 발전하는 분야입니다. 최신 기술 동향을 파악하고 실무에 적용할 수 있는 프로그래밍 스킬을 개발해야 합니다.</p>
                
                <h2>프론트엔드 개발 기초</h2>
                <p>프론트엔드 개발에서는 사용자 경험을 중시해야 합니다. 반응형 디자인과 접근성을 고려한 웹 인터페이스를 구현하세요.</p>
                
                <h3>자바스크립트 프레임워크</h3>
                <p>React, Vue, Angular와 같은 프레임워크를 활용하면 효율적인 개발이 가능합니다. 프로젝트 요구사항에 맞는 도구를 선택하세요.</p>
                
                <h2>백엔드 개발 패턴</h2>
                <p>확장 가능한 백엔드 시스템을 구축하려면 적절한 아키텍처 패턴을 적용해야 합니다. 데이터베이스 설계와 API 개발에 신경 쓰세요.</p>
                
                <p>보안은 웹개발에서 매우 중요한 요소입니다. 입력값 검증, 인증 시스템, 데이터 암호화를 적절히 구현하세요.</p>
                
                <img src="korean-web-dev.jpg" alt="한국어 웹개발 기술 스택">
                
                <a href="/frontend-korean" title="프론트엔드 개발 가이드">프론트엔드 가이드</a>
                <a href="/backend-korean" title="백엔드 개발 패턴">백엔드 개발</a>
            </body>
            </html>
        ';
    }

    private function createLargeHtmlContent(): string
    {
        $content = '
            <!DOCTYPE html>
            <html>
            <head>
                <title>Comprehensive Web Development Encyclopedia</title>
                <meta name="description" content="The ultimate resource for web development covering all aspects of modern programming.">
            </head>
            <body>
                <h1>Comprehensive Web Development Encyclopedia</h1>
        ';

        // Generate large amount of content
        for ($i = 1; $i <= 50; $i++) {
            $content .= "
                <h2>Section {$i}: Advanced Development Topic</h2>
                <p>This section covers advanced web development concepts that are essential for modern programming. Understanding these principles helps create robust, scalable applications that meet industry standards and user expectations.</p>
                
                <h3>Subsection {$i}.1: Technical Implementation</h3>
                <p>Detailed technical implementation requires careful planning and systematic approach. Consider performance implications, security requirements, and maintainability when designing system architecture. Modern development practices emphasize clean code, proper documentation, and comprehensive testing strategies.</p>
                
                <h3>Subsection {$i}.2: Best Practices</h3>
                <p>Industry best practices evolve continuously with technology advancement. Stay updated with latest frameworks, tools, and methodologies. Implement code reviews, automated testing, and continuous integration pipelines for reliable software delivery.</p>
                
                <p>Quality assurance processes ensure application reliability and user satisfaction. Monitor performance metrics, handle errors gracefully, and provide meaningful feedback to users throughout their journey.</p>
            ";
        }

        $content .= '</body></html>';
        return $content;
    }

    private function extractDataFromHtml(string $html): array
    {
        // Simplified extraction for testing
        preg_match('/<title>(.*?)<\/title>/', $html, $titleMatches);
        preg_match('/<meta name="description" content="(.*?)"/', $html, $descMatches);

        return [
            'title' => $titleMatches[1] ?? null,
            'meta_description' => $descMatches[1] ?? null,
            'images' => [],
            'internal_links' => [],
            'external_links' => [],
            'meta_tags' => [],
            'headings' => [],
        ];
    }
}