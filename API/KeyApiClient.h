#import <Foundation/Foundation.h>

@interface KeyAPIClient : NSObject

@property (nonatomic, strong) NSString *apiBase;
@property (nonatomic, strong) NSString *accessToken;

- (void)setAccessToken:(NSString *)accessToken;

- (KeyAPIClientResponseCode)apiRequest:(NSString *)apiPath params:(NSDictionary *)params;

- (void)onSuccess:(void (^)(void))completeBlock;

- (void)execute;

@end
