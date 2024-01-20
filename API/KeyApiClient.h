#import <Foundation/Foundation.h>

typedef NS_ENUM(NSInteger, KeyAPIClientResponseCode) {
    KeyAPIClientResponseCodeSuccess = 0,
    KeyAPIClientResponseCodeFail = 1,
};

@interface KeyAPIClient : NSObject

@property (nonatomic, strong) NSString *apiBase;
@property (nonatomic, strong) NSString *accessToken;

- (void)setAccessToken:(NSString *)accessToken;

- (KeyAPIClientResponseCode)apiRequest:(NSString *)apiPath params:(NSDictionary *)params;

- (KeyAPIClientResponseCode)checkUdid;

- (KeyAPIClientResponseCode)requestUdid;

- (KeyAPIClientResponseCode)checkKey:(NSString *)key;

- (void)onSuccess:(void (^)(void))completeBlock;

- (void)execute;

@end
