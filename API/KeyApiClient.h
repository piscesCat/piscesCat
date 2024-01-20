#import <Foundation/Foundation.h>

@interface APIClient : NSObject

@property (nonatomic, strong) NSString *apiBase;
@property (nonatomic, strong) NSString *accessToken;

- (void)setApiAccessToken:(NSString *)accessToken;

- (void)onSuccess:(void (^)(void))onsuccess;

@end
