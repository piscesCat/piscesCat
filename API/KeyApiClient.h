#import <Foundation/Foundation.h>

typedef NS_ENUM(NSInteger, APIClientStatus) {
  APIClientStatusSuccess,
  APIClientStatusFail,
};

@interface KeyApiClient : NSObject

@property (nonatomic, strong, readonly) NSString *apiBase;
@property (nonatomic, strong, readonly) NSString *apiAccessToken;

+ (APIClient *)sharedClient;

- (void)setApiAccessToken:(NSString *)apiAccessToken;

- (APIClientStatus)apiRequest:(NSDictionary *)params;

- (APIClientStatus)checkUdid;

- (APIClientStatus)requestUdid;

- (APIClientStatus)checkKey:(NSString *)key value:(NSString *)value;

- (void)onSuccess:(void (^)(void))onsuccess;

- (void)execute;

@end