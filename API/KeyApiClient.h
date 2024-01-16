#import <Foundation/Foundation.h>
#import <UIKit/UIKit.h>
#import <Metal/Metal.h>

NS_ASSUME_NONNULL_BEGIN

@interface KeyApiClient : NSObject
- (void) onSuccess:(void (^)(void))callback;
- (void) setSecretKey:(NSString*)secretKey;
- (NSString*) getUDID;

@end

NS_ASSUME_NONNULL_END