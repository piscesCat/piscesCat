#import "KeyApiClient.h"

@interface KeyApiClient ()

@property (nonatomic, strong) NSString *udid;
@property (nonatomic, strong) NSString *secretKey;
@property (nonatomic, strong) NSString *apiBaseUrl;
@property (nonatomic, assign) NSTimeInterval dataCryptExpireTime;

- (NSString *)getVendorIdentifier;
- (void)execute:(void (^)(void))callback;
- (void)checkUdid:(void (^)(void))callback;
- (void)requestUdid;
- (NSString *)generateCryptKey:(NSTimeInterval)expireTime;
- (NSData *)dataEncrypt:(NSDictionary *)data;
- (NSDictionary *)dataDecrypt:(NSString *)encryptedData expiresTime:(NSTimeInterval)expiresTime;
- (NSString *)utf8Encode:(NSString *)string;
- (NSDictionary *)apiRequest:(NSString *)apiPath postData:(NSDictionary *)postData;

@end

@implementation KeyApiClient

- (instancetype)init {
    self = [super init];
    if (self) {
        self.apiBaseUrl = @"https://khaiphan.vercel.app/api-v1";
        self.dataCryptExpireTime = [[NSDate date] timeIntervalSince1970] + 15; // 15 seconds
    }
    return self;
}

- (NSString *)getUdid {
    return self.udid;
}

- (void)setSecretKey:(NSString *)secretKey {
    self.secretKey = secretKey;
}

- (void)onSuccess:(void (^)(void))callback {
    [self execute:callback];
}

- (NSString *)getVendorIdentifier {
    NSString *vendorID = [[[UIDevice currentDevice] identifierForVendor] UUIDString];
    return vendorID;
}

- (void)execute:(void (^)(void))callback {
    [self checkUdid:callback];
}

- (void)checkUdid:(void (^)(void))callback {
    NSDictionary *apiData = [self apiRequest:@"/check_udid" postData:@{@"device_vendor_id": [self getVendorIdentifier]}];

    if ([apiData[@"status"] isEqualToString:@"success"]) {
        self.udid = apiData[@"udid"];
        callback();
    } else {
        [self requestUdid];
    }
}

- (void)requestUdid {
    NSDictionary *apiData = [self apiRequest:@"/request_udid" postData:@{@"device_vendor_id": [self getVendorIdentifier]}];

    if ([apiData[@"status"] isEqualToString:@"success"]) {
        // Mở ở trình duyệt
        NSString *mobileConfigURLString = apiData[@"mobile_config_url"];
        NSURL *mobileConfigURL = [NSURL URLWithString:mobileConfigURLString];

        if (mobileConfigURL) {
            if ([[UIApplication sharedApplication] canOpenURL:mobileConfigURL]) {
                [[UIApplication sharedApplication] openURL:mobileConfigURL options:@{} completionHandler:nil];
            } else {
                NSLog(@"Could not open URL in Safari. URL is not supported.");
            }
        } else {
            NSLog(@"Invalid URL");
        }
    } else {
        NSLog(@"API request failed with status: %@", apiData[@"status"]);
    }
}

- (NSString *)generateCryptKey:(NSTimeInterval)expireTime {
    if (expireTime == 0) {
        expireTime = self.dataCryptExpireTime;
    }
    return [self md5:[NSString stringWithFormat:@"%@%.0f", self.secretKey, expireTime]];
}

- (NSDictionary *)dataEncrypt:(id)data {
    if ([data isKindOfClass:[NSArray class]]) {
        data = [self jsonEncode:data];
    }
    
    NSString *key = [self generateCryptKey:0];
    NSString *plainTextBytes = [self utf8Encode:data];
    NSString *keyBytes = [self utf8Encode:key];
    NSMutableData *encryptedBytes = [NSMutableData data];

    for (int i = 0; i < plainTextBytes.length; i++) {
        char encryptedChar = [plainTextBytes characterAtIndex:i] ^ [keyBytes characterAtIndex:i % keyBytes.length];
        [encryptedBytes appendBytes:&encryptedChar length:1];
    }

    NSString *encryptedData = [self base64Encode:encryptedBytes];
    return @{@"data": encryptedData, @"expires_time": @(self.dataCryptExpireTime)};
}

- (id)dataDecrypt:(NSString *)encryptedData expiresTime:(NSTimeInterval)expiresTime {
    NSString *key = [self generateCryptKey:expiresTime];
    NSData *encryptedBytes = [self base64Decode:encryptedData];
    NSString *keyBytes = [self utf8Encode:key];
    NSMutableData *decryptedBytes = [NSMutableData dataWithCapacity:encryptedBytes.length];

    for (int i = 0; i < encryptedBytes.length; i++) {
        char decryptedChar = ((char *)encryptedBytes.bytes)[i] ^ [keyBytes characterAtIndex:i % keyBytes.length];
        [decryptedBytes appendBytes:&decryptedChar length:1];
    }

    NSError *error;
    id decryptedData = [NSJSONSerialization JSONObjectWithData:decryptedBytes options:kNilOptions error:&error];
    return error ? encryptedData : decryptedData;
}

- (NSString *)utf8Encode:(NSString *)string {
    return [string stringByAddingPercentEncodingWithAllowedCharacters:[NSCharacterSet URLHostAllowedCharacterSet]];
}

- (NSDictionary *)apiRequest:(NSString *)apiPath postData:(NSDictionary *)postData {
    NSString *urlString = [NSString stringWithFormat:@"%@%@", self.apiBaseUrl, apiPath];
    NSURL *url = [NSURL URLWithString:urlString];
    NSMutableURLRequest *request = [NSMutableURLRequest requestWithURL:url];
    request.HTTPMethod = @"POST";
    
    NSData *encryptedData = [self dataEncrypt:postData];
    request.HTTPBody = encryptedData;

    NSURLSessionDataTask *task = [[NSURLSession sharedSession] dataTaskWithRequest:request completionHandler:^(NSData *data, NSURLResponse *response, NSError *error) {
        if (error == nil) {
            NSDictionary *arrayResp = [NSJSONSerialization JSONObjectWithData:data options:kNilOptions error:nil];
            NSDictionary *dataDecrypted = [self dataDecrypt:arrayResp[@"data"] expiresTime:[arrayResp[@"expires_time"] doubleValue]];
            // Handle decrypted data as needed
        } else {
            NSLog(@"Error: %@", error.localizedDescription);
        }
    }];
    
    [task resume];
    return nil; // Adjust return type as needed
}

@end