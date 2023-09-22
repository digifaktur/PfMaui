using Android.App;
using Android.Content;
using Android.Content.PM;
using Android.OS;
using Android.Views;

namespace PfMaui;

[Activity(Theme = "@style/Maui.SplashTheme", MainLauncher = true, ConfigurationChanges = ConfigChanges.ScreenSize | ConfigChanges.Orientation | ConfigChanges.UiMode | ConfigChanges.ScreenLayout | ConfigChanges.SmallestScreenSize | ConfigChanges.Density)]
public class MainActivity : MauiAppCompatActivity
{
    bool displayRequested = false;
    public static MainActivity MainActivityInstance { get; private set; }
    protected override void OnCreate(Bundle savedInstanceState)
    {
        base.OnCreate(savedInstanceState);
        Platform.Init(this, savedInstanceState);
        MainActivityInstance = this;
        this.Window.AddFlags(WindowManagerFlags.Fullscreen);
        this.RequestedOrientation = ScreenOrientation.Landscape;
    }

    public void OpenAppSettings()
    {
        var intent = new Intent(Android.Provider.Settings.ActionSettings);
        intent.AddFlags(ActivityFlags.NewTask);
        StartActivity(intent);
    }

    public void SetDisplayBrightness(int brightness)
    {
        if (Build.VERSION.SdkInt >= BuildVersionCodes.M)
        {
            if (!Android.Provider.Settings.System.CanWrite(this) && !displayRequested)
            {
                displayRequested = true;
                Intent intent = new Intent(Android.Provider.Settings.ActionManageWriteSettings);
                intent.SetData(Android.Net.Uri.Parse("package:" + PackageName));
                StartActivity(intent);
            }
            else
            {
                Android.Provider.Settings.System.PutInt(this.ContentResolver,
                    Android.Provider.Settings.System.ScreenBrightness, brightness);
            }
        }
    }
}
