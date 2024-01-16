#import "KeyApiClient.h"
#import <CommonCrypto/CommonDigest.h>

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
- (NSString *)jsonDecode:(NSString *)jsonString;
- (NSString *)jsonEncode:(id)data;
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
        // Hiện ô hỏi lấy UDID ở đây nếu đồng ý chạy dòng code dưới
        [self requestUdid];
    }
}

- (void)requestUdid {
    NSDictionary *apiData = [self apiRequest:@"/request_udid" postData:@{@"device_vendor_id": [self getVendorIdentifier]}];

    if ([apiData[@"status"] isEqualToString:@"success"]) {
        NSString *mobileConfigURLString = apiData[@"mobile_config_url"];
        NSURL *mobileConfigURL = [NSURL URLWithString:mobileConfigURLString];

        if ([[UIApplication sharedApplication] canOpenURL:mobileConfigURL]) {
            [[UIApplication sharedApplication] openURL:mobileConfigURL options:@{} completionHandler:nil];
        } else {
            NSLog(@"Không thể mở URL mobile config.");
        }
    } else {
        NSLog(@"API status: %@", apiData[@"status"]);
    }
}

- (NSString *)generateCryptKey:(NSNumber *)expireTime {
    if (expireTime == nil) {
        expireTime = [NSNumber numberWithInteger:self.dataCryptExpireTime];
    }
    NSString *key = [self md5:[NSString stringWithFormat:@"%@%@", self.secretKey, expireTime]];
    return key;
}

- (NSString *)dataEncrypt:(id)data {
    if ([data isKindOfClass:[NSArray class]] || [data isKindOfClass:[NSDictionary class]]) {
        data = [self jsonEncode:data];
    }
    
    NSString *key = [self generateCryptKey:nil];
    NSData *plainTextBytes = [self utf8Encode:data];
    NSData *keyBytes = [self utf8Encode:key];
    NSMutableData *encryptedBytes = [NSMutableData data];

    for (int i = 0; i < plainTextBytes.length; i++) {
        char plainChar, keyChar;
        [plainTextBytes getBytes:&plainChar range:NSMakeRange(i, 1)];
        [keyBytes getBytes:&keyChar range:NSMakeRange(i % keyBytes.length, 1)];

        char encryptedChar = plainChar ^ keyChar;
        [encryptedBytes appendBytes:&encryptedChar length:1];
    }

    NSString *encryptedData = [encryptedBytes base64EncodedStringWithOptions:0];
    return @{@"data": encryptedData, @"expires_time": [NSNumber numberWithInteger:self.dataCryptExpireTime]};
}

- (id)dataDecrypt:(NSString *)encryptedData expireTime:(NSNumber *)expiresTime {
    NSString *key = [self generateCryptKey:expiresTime];
    NSData *encryptedText = [[NSData alloc] initWithBase64EncodedString:encryptedData options:0];
    NSData *encryptedBytes = [encryptedText bytes];
    NSData *keyBytes = [self utf8Encode:key];
    NSMutableData *decryptedBytes = [NSMutableData data];

    for (int i = 0; i < encryptedBytes.length; i++) {
        char encryptedChar, keyChar;
        [encryptedBytes getBytes:&encryptedChar range:NSMakeRange(i, 1)];
        [keyBytes getBytes:&keyChar range:NSMakeRange(i % keyBytes.length, 1)];

        char decryptedChar = encryptedChar ^ keyChar;
        [decryptedBytes appendBytes:&decryptedChar length:1];
    }

    NSString *decryptedString = [[NSString alloc] initWithData:decryptedBytes encoding:NSUTF8StringEncoding];
    id decryptedData = [self jsonDecode:decryptedString];

    return decryptedData ?: decryptedString;
}

- (NSData *)utf8Encode:(NSString *)string {
    return [string dataUsingEncoding:NSUTF8StringEncoding];
}

- (NSString *)md5:(NSString *)input {
    const char *cStr = [input UTF8String];
    unsigned char digest[CC_MD5_DIGEST_LENGTH];
    CC_MD5(cStr, (CC_LONG)strlen(cStr), digest);

    NSMutableString *output = [NSMutableString stringWithCapacity:CC_MD5_DIGEST_LENGTH * 2];
    for (int i = 0; i < CC_MD5_DIGEST_LENGTH; i++) {
        [output appendFormat:@"%02x", digest[i]];
    }

    return output;
}

- (NSString *)jsonEncode:(id)data {
    NSError *jsonError;
    NSData *jsonData = [NSJSONSerialization dataWithJSONObject:data options:0 error:&jsonError];

    if (jsonError) {
        return nil;
    } else {
        return [[NSString alloc] initWithData:jsonData encoding:NSUTF8StringEncoding];
    }
}

- (NSString *)jsonDecode:(NSString *)jsonString {
    NSData *jsonData = [jsonString dataUsingEncoding:NSUTF8StringEncoding];
    NSError *jsonError;
    id decodedData = [NSJSONSerialization JSONObjectWithData:jsonData options:kNilOptions error:&jsonError];

    return jsonError ? nil : decodedData;
}

- (NSDictionary *)apiRequest:(NSString *)apiPath postData:(NSDictionary *)postData {
    NSString *urlString = [NSString stringWithFormat:@"%@%@", self.apiBaseUrl, apiPath];
    NSURL *url = [NSURL URLWithString:urlString];
    NSMutableURLRequest *request = [NSMutableURLRequest requestWithURL:url];
    request.HTTPMethod = @"POST";

    NSData *encryptedData = [self dataEncrypt:postData];
    request.HTTPBody = encryptedData;

    NSURLSession *session = [NSURLSession sharedSession];
    NSURLSessionDataTask *task = [session dataTaskWithRequest:request completionHandler:^(NSData *data, NSURLResponse *response, NSError *error) {
        if (error == nil) {
            NSString *jsonString = [[NSString alloc] initWithData:data encoding:NSUTF8StringEncoding];
            id decodedData = [self jsonDecode:jsonString];

            if (decodedData != nil && [decodedData isKindOfClass:[NSDictionary class]]) {
                NSDictionary *arrayResp = decodedData;
                NSDictionary *dataDecrypted = [self dataDecrypt:arrayResp[@"data"] expiresTime:[arrayResp[@"expires_time"] doubleValue]];
            } else {
                NSLog(@"Error decoding JSON");
            }
        } else {
            NSLog(@"Error: %@", error.localizedDescription);
        }
    }];

    [task resume];
    return nil;
}

@end