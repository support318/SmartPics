# AI Alt Text Generator

An advanced WordPress plugin that automatically generates SEO-optimized alt text, captions, and schema markup for images using multiple AI providers with intelligent caching and content analysis.

## Features

### 🤖 Multi-Provider AI Support
- **Google Vertex AI (Gemini Pro Vision)** - Primary AI provider with advanced visual understanding
- **OpenAI GPT-4 Vision** - Secondary fallback with excellent image analysis capabilities  
- **Anthropic Claude 3 Vision** - Tertiary fallback for comprehensive coverage
- **Automatic Failover** - Seamless switching between providers if one fails
- **Cost Optimization** - Intelligent routing based on provider performance and cost

### 🎯 Advanced Content Intelligence
- **Content Context Analysis** - Analyzes surrounding page content for relevant context
- **Topic Modeling** - AI-powered identification of page themes and concepts
- **SEO Integration** - Deep integration with RankMath and Yoast SEO plugins
- **Focus Keyword Integration** - Automatically incorporates SEO focus keywords
- **Sentiment Analysis** - Matches alt text tone to content sentiment

### 🌍 Geotargeting & Local SEO  
- **Google My Business Integration** - Connect GMB profiles for location data
- **Vector Database RAG** - Advanced retrieval-augmented generation for location context
- **Local Business Schema** - Automatic generation of location-specific schema markup
- **Dynamic Location Data** - Real-time business information integration

### ⚡ Performance & Optimization
- **Smart Caching System** - Reduces API costs by 60-80% through intelligent caching
- **Similarity Detection** - Perceptual hashing to identify and reuse similar images
- **Bulk Processing** - Queue-based system for processing entire image libraries
- **Pre-filtering** - Skip already-optimized images to save resources
- **Batch Optimization** - Process multiple images efficiently

### 📊 Schema Markup Automation
- **JSON-LD Generation** - Automatic structured data for better search visibility
- **Multiple Schema Types** - Support for ImageObject, Product, Article, Organization
- **Dynamic Schema** - Context-aware schema based on content type
- **SEO Enhancement** - Improved search engine understanding

### 🛠️ Developer Features
- **Cloudflare Integration** - R2 storage and Workers for scalable processing  
- **REST API** - Full API access for custom integrations
- **Webhooks** - Real-time notifications for processing events
- **Custom Prompts** - Configurable AI prompts for specific use cases
- **Comprehensive Logging** - Detailed analytics and performance monitoring

## Installation

1. **Download & Upload**
   ```bash
   # Clone from GitHub
   git clone https://github.com/candidstudios/ai-alt-text-generator.git
   
   # Or download and extract to wp-content/plugins/
   ```

2. **Activate Plugin**
   - Go to WordPress Admin → Plugins
   - Find "AI Alt Text Generator" and click "Activate"

3. **Configure API Keys**
   - Navigate to AI Alt Text → Settings
   - Add at least one AI provider API key:
     - **Vertex AI**: Project ID, Location, API Key
     - **OpenAI**: API Key  
     - **Claude**: API Key
   - Configure additional settings as needed

## Quick Setup Guide

### Minimum Configuration
1. **AI Provider** - Add API key for at least one provider
2. **Test Connection** - Use the "Test Connection" button to verify setup
3. **Enable Auto-Processing** - Turn on automatic processing for new uploads

### Recommended Configuration  
1. **Multiple Providers** - Configure 2-3 AI providers for redundancy
2. **SEO Integration** - Enable RankMath/Yoast integration if using these plugins
3. **Caching** - Enable similarity detection and caching (enabled by default)
4. **Schema Markup** - Enable automatic schema generation
5. **Content Analysis** - Enable content context analysis for better results

### Advanced Configuration
1. **Cloudflare Setup** - Configure R2 and Workers for enterprise scale
2. **Geotargeting** - Connect Google My Business and vector database
3. **Custom Prompts** - Define industry-specific prompts in JSON format
4. **Bulk Processing** - Set batch sizes and rate limits for large libraries

## Usage

### Automatic Processing
- **New Uploads**: Images are automatically processed when uploaded (if enabled)
- **Background Processing**: Uses WordPress cron for non-blocking operations
- **Smart Detection**: Skips images that already have alt text

### Manual Processing
- **Single Image**: Use "Generate Alt Text" button in Media Library
- **Bulk Processing**: Go to AI Alt Text → Bulk Processing to process entire library
- **Queue Management**: Monitor processing jobs in the dashboard

### Integration with Page Builders
- **Elementor**: Automatically works with Elementor image widgets
- **Gutenberg**: Full support for WordPress block editor
- **Classic Editor**: Works with traditional WordPress media insertion

## API Configuration

### Google Vertex AI Setup
1. Create Google Cloud Project
2. Enable Vertex AI API
3. Create service account with Vertex AI permissions
4. Generate API key
5. Configure project ID and region in settings

### OpenAI Setup  
1. Sign up at OpenAI
2. Generate API key with GPT-4V access
3. Add key to plugin settings
4. Monitor usage in OpenAI dashboard

### Anthropic Claude Setup
1. Sign up at Anthropic
2. Generate API key
3. Add key to plugin settings
4. Configure usage limits as needed

## Cloudflare Configuration

### R2 Storage Setup
1. Create Cloudflare account
2. Set up R2 bucket for image storage
3. Generate R2 API tokens
4. Configure bucket name and credentials

### Workers Setup
1. Deploy provided Worker scripts
2. Configure Worker endpoints
3. Set up trigger events for image processing
4. Monitor Worker analytics

## Performance Optimization

### Caching Strategy
- **Similarity Caching**: Reuse alt text for visually similar images
- **Response Caching**: Cache AI provider responses for repeated requests
- **Batch Processing**: Group similar requests to reduce API calls
- **Cache Expiration**: Configurable cache duration (default 30 days)

### Cost Management
- **Provider Rotation**: Automatic selection of most cost-effective provider
- **Usage Monitoring**: Real-time tracking of API costs
- **Rate Limiting**: Prevent exceeding API quotas
- **Cache Analytics**: Monitor cache hit rates and savings

## Troubleshooting

### Common Issues

**API Connection Fails**
- Verify API keys are correct
- Check network connectivity
- Confirm API quotas not exceeded
- Test with "Test Connection" button

**Images Not Processing**
- Check if auto-processing is enabled
- Verify at least one AI provider is configured
- Look for error messages in dashboard
- Check WordPress cron is working

**Poor Alt Text Quality**
- Review content analysis settings
- Check if focus keywords are being detected
- Consider custom prompts for your industry
- Verify page content is substantial enough for context

### Debug Mode
Enable debug mode in settings to get detailed logging:
1. Go to AI Alt Text → Settings → Advanced
2. Enable "Debug Mode"
3. Check logs in dashboard or debug.log file

## Contributing

We welcome contributions! Please see our [Contributing Guidelines](CONTRIBUTING.md) for details.

### Development Setup
```bash
# Clone repository
git clone https://github.com/candidstudios/ai-alt-text-generator.git

# Install dependencies (if any)
composer install
npm install

# Set up development environment
cp .env.example .env
```

## Support

- **Documentation**: [Full documentation](https://docs.candidstudios.net/ai-alt-text-generator)
- **Issues**: [GitHub Issues](https://github.com/candidstudios/ai-alt-text-generator/issues)
- **Support Forum**: [WordPress.org Support](https://wordpress.org/support/plugin/ai-alt-text-generator)

## License

This plugin is licensed under the GPL v2 or later.

## Changelog

### Version 1.0.0
- Initial release
- Multi-provider AI support (Vertex AI, OpenAI, Claude)
- Advanced content analysis and topic modeling
- Intelligent caching and similarity detection
- SEO plugin integration (RankMath, Yoast)
- Automatic schema markup generation
- Bulk processing capabilities
- Comprehensive admin dashboard
- Cloudflare integration foundation
- Geotargeting framework (GMB + Vector DB)

---

**Developed by [Candid Studios](https://candidstudios.net)** - Advanced WordPress solutions for modern businesses.