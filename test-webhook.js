/**
 * Hospitable Webhook Test Script
 * Tests webhook handler - Update the WEBHOOK_URL below with your actual URL
 */

const http = require('http');
const https = require('https');

const WEBHOOK_URL = 'https://your-domain.com/webhook-path/'; // ⚠️ Replace with your actual webhook URL

/**
 * Send test webhook
 */
function sendWebhook(payload, description) {
    return new Promise((resolve, reject) => {
        const url = new URL(WEBHOOK_URL);
        const protocol = url.protocol === 'https:' ? https : http;
        
        const data = JSON.stringify(payload);
        
        const options = {
            hostname: url.hostname,
            path: url.pathname || '/',
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'Content-Length': Buffer.byteLength(data)
            }
        };
        
        const req = protocol.request(options, (res) => {
            let responseData = '';
            
            res.on('data', (chunk) => {
                responseData += chunk;
            });
            
            res.on('end', () => {
                console.log(`\n✅ ${description}`);
                console.log(`   Status: ${res.statusCode}`);
                console.log(`   Response: ${responseData}`);
                resolve({ status: res.statusCode, data: responseData });
            });
        });
        
        req.on('error', (error) => {
            console.error(`\n❌ ${description}`);
            console.error(`   Error: ${error.message}`);
            reject(error);
        });
        
        req.write(data);
        req.end();
    });
}

/**
 * Property Created Webhook
 */
const propertyCreated = {
    body: {
        id: '497f6eca-6276-4993-bfeb-53cbbbba6f08',
        action: 'property.created',
        data: {
            id: '550e8400-e29b-41d4-a716-446655440000',
            name: 'Beachfront Villa',
            address: 'Miami, FL'
        },
        created: '2024-10-08T07:03:34Z',
        version: 'v2'
    }
};

/**
 * Property Deleted Webhook
 */
const propertyDeleted = {
    body: {
        id: '497f6eca-6276-4993-bfeb-53cbbbba6f08',
        action: 'property.deleted',
        data: {
            id: '550e8400-e29b-41d4-a716-446655440000'
        },
        created: '2024-10-08T07:03:34Z',
        version: 'v2'
    }
};

/**
 * Property Merged Webhook
 */
const propertyMerged = {
    body: {
        id: '497f6eca-6276-4993-bfeb-53cbbbba6f08',
        action: 'property.merged',
        data: {
            previous_id: '550e8400-e29b-41d4-a716-446655440001',
            new_id: '550e8400-e29b-41d4-a716-446655440000'
        },
        created: '2024-10-08T07:03:34Z',
        version: 'v2'
    }
};

/**
 * Property Changed Webhook
 */
const propertyChanged = {
    body: {
        id: '497f6eca-6276-4993-bfeb-53cbbbba6f08',
        action: 'property.changed',
        data: {
            id: '550e8400-e29b-41d4-a716-446655440000',
            name: 'Beachfront Villa Updated',
            address: 'Miami, FL'
        },
        triggers: ['name'],
        created: '2024-10-08T07:03:34Z',
        version: 'v2'
    }
};

/**
 * Run all tests
 */
async function runTests() {
    console.log('🚀 Starting Hospitable Webhook Tests...\n');
    console.log(`Target: ${WEBHOOK_URL}\n`);
    
    try {
        await sendWebhook(propertyCreated, 'Test 1: Property Created');
        await new Promise(resolve => setTimeout(resolve, 1000));
        
        await sendWebhook(propertyChanged, 'Test 2: Property Changed');
        await new Promise(resolve => setTimeout(resolve, 1000));
        
        await sendWebhook(propertyDeleted, 'Test 3: Property Deleted');
        await new Promise(resolve => setTimeout(resolve, 1000));
        
        await sendWebhook(propertyMerged, 'Test 4: Property Merged');
        
        console.log('\n✅ All tests completed!\n');
    } catch (error) {
        console.error('\n❌ Test suite failed:', error.message);
        process.exit(1);
    }
}

// Run tests
runTests();
