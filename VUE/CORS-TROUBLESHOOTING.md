# CORS Error Troubleshooting Guide

## Common CORS Issues and Solutions

### 1. **Origin Not Allowed**
**Error**: `Access to fetch at 'http://localhost:8000/api/test' from origin 'http://localhost:8080' has been blocked by CORS policy: The request client is not a secure context and the resource is on a different origin.`

**Solution**: Ensure your frontend origin is in the `allowed_origins` list in `laravel-backend/config/cors.php`

### 2. **Missing Headers**
**Error**: `Request header field authorization is not allowed by Access-Control-Allow-Headers in preflight response.`

**Solution**: Add missing headers to `allowed_headers` in CORS config:
```php
'allowed_headers' => ['Accept', 'Authorization', 'Content-Type', 'Origin', 'X-Requested-With', 'X-CSRF-TOKEN', 'X-XSRF-TOKEN']
```

### 3. **Credentials Not Supported**
**Error**: `The value of the 'Access-Control-Allow-Credentials' header in the response is '' which must be 'true' when the request's credentials mode is 'include'.`

**Solution**: Set `supports_credentials` to `true` in CORS config

### 4. **Method Not Allowed**
**Error**: `Method PUT is not allowed by Access-Control-Allow-Methods in preflight response.`

**Solution**: Ensure `allowed_methods` includes all needed HTTP methods

## Testing Steps

1. **Check Laravel Backend is Running**:
   ```bash
   cd laravel-backend
   php artisan serve --host=0.0.0.0 --port=8000
   ```

2. **Check Frontend is Running**:
   ```bash
   npm run dev
   # Should run on http://localhost:8080
   ```

3. **Test CORS Configuration**:
   - Open `test-cors.html` in your browser
   - Check browser console for CORS errors
   - Verify CORS headers in Network tab

4. **Verify API Endpoints**:
   ```bash
   curl -H "Origin: http://localhost:8080" \
        -H "Access-Control-Request-Method: GET" \
        -H "Access-Control-Request-Headers: authorization" \
        -X OPTIONS \
        http://localhost:8000/api/test
   ```

## Environment Variables

You can override CORS settings with environment variables:

```env
CORS_ALLOWED_ORIGINS=http://localhost:8080,http://localhost:3000,http://localhost:5173
CORS_ALLOWED_HEADERS=Accept,Authorization,Content-Type,Origin,X-Requested-With
CORS_SUPPORTS_CREDENTIALS=true
CORS_MAX_AGE=600
```

## Debug Commands

```bash
# Check current CORS config
php artisan config:show cors

# Clear config cache
php artisan config:clear

# Check routes
php artisan route:list --path=api
```

## Common Frontend Issues

1. **Wrong Base URL**: Ensure `src/config/api.js` points to correct backend URL
2. **Missing Headers**: Check that API client includes proper headers
3. **Credentials Mode**: Ensure `credentials: 'include'` is set for authenticated requests

## Quick Fixes

### For Development:
1. Add your frontend URL to `allowed_origins`
2. Include all necessary headers in `allowed_headers`
3. Set `supports_credentials` to `true`
4. Restart Laravel backend server

### For Production:
1. Set proper environment variables
2. Use specific origins instead of wildcards
3. Configure proper headers for your frontend framework

