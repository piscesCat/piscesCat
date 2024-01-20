#import <Foundation/Foundation.h>

@interface KeyAPIClient : NSObject

@property (nonatomic, strong, readonly) NSString *apiBase;
@property (nonatomic, strong, readonly) NSString *accessToken;

+ (instancetype)sharedClient;

- (void)setAccessToken:(NSString *)accessToken;

- (void)apiRequestWithPath:(NSString *)apiPath
                 params:(NSDictionary *)params
               completion:(void (^)(id response, NSError *error))completion;

- (void)onSuccess:(void (^)(void))completion;

- (enum KeyAPIClientStatus)checkUdid;
- (enum KeyAPIClientStatus)requestUdid;
- (enum KeyAPIClientStatus)checkKey:(NSString *)key;
- (enum KeyAPIClientStatus)execute;

typedef NS_ENUM(NSUInteger, KeyAPIClientStatus) {
    KeyAPIClientStatusSuccess,
    KeyAPIClientStatusFail,
};

@end
