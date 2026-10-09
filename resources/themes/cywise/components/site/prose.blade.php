{{--
  Long-form HTML from the DB (blog posts, CMS pages, changelog), styled through descendant selectors.

  <x-site.prose class="ui:mt-12">{!! $post->body !!}</x-site.prose>
--}}
<div {{ $attributes->merge(['class' => '
    ui:max-w-[720px] ui:text-[19px] ui:leading-[1.7] ui:text-slate-800 ui:break-words
    ui:[&>*:first-child]:mt-0
    ui:[&_p]:m-0 ui:[&_p]:mt-6
    ui:[&_h2]:m-0 ui:[&_h2]:mt-14 ui:[&_h2]:text-[clamp(26px,3.4vw,36px)] ui:[&_h2]:font-black ui:[&_h2]:uppercase ui:[&_h2]:leading-none ui:[&_h2]:tracking-tight ui:[&_h2]:text-ink ui:[&_h2]:font-stretch-112%
    ui:[&_h3]:m-0 ui:[&_h3]:mt-10 ui:[&_h3]:text-2xl ui:[&_h3]:font-extrabold ui:[&_h3]:leading-tight ui:[&_h3]:tracking-tight ui:[&_h3]:text-ink
    ui:[&_h4]:m-0 ui:[&_h4]:mt-8 ui:[&_h4]:text-xl ui:[&_h4]:font-bold ui:[&_h4]:text-ink
    ui:[&_a]:text-brand-700! ui:[&_a]:underline! ui:[&_a]:underline-offset-4 ui:[&_a:hover]:text-ink!
    ui:[&_strong]:font-bold ui:[&_strong]:text-ink
    ui:[&_ul]:m-0 ui:[&_ul]:mt-6 ui:[&_ul]:list-disc ui:[&_ul]:pl-6 ui:[&_ol]:m-0 ui:[&_ol]:mt-6 ui:[&_ol]:list-decimal ui:[&_ol]:pl-6 ui:[&_li]:mt-2 ui:[&_li]:pl-1 ui:[&_li::marker]:text-brand-500
    ui:[&_blockquote]:m-0 ui:[&_blockquote]:mt-8 ui:[&_blockquote]:border-0 ui:[&_blockquote]:border-l-6 ui:[&_blockquote]:border-solid ui:[&_blockquote]:border-brand-500 ui:[&_blockquote]:pl-6 ui:[&_blockquote]:text-[22px] ui:[&_blockquote]:font-semibold ui:[&_blockquote]:leading-snug ui:[&_blockquote]:text-ink
    ui:[&_code]:font-mono ui:[&_code]:text-[0.85em] ui:[&_:not(pre)>code]:rounded-md ui:[&_:not(pre)>code]:bg-slate-100 ui:[&_:not(pre)>code]:px-1.5 ui:[&_:not(pre)>code]:py-0.5
    ui:[&_pre]:m-0 ui:[&_pre]:mt-8 ui:[&_pre]:overflow-x-auto ui:[&_pre]:rounded-2xl ui:[&_pre]:bg-ink ui:[&_pre]:p-6 ui:[&_pre]:font-mono ui:[&_pre]:text-sm ui:[&_pre]:leading-relaxed ui:[&_pre]:text-slate-100 ui:[&_pre_code]:text-[1em]
    ui:[&_img]:mt-8 ui:[&_img]:h-auto ui:[&_img]:rounded-2xl
    ui:[&_hr]:my-12 ui:[&_hr]:border-0 ui:[&_hr]:border-t ui:[&_hr]:border-solid ui:[&_hr]:border-line
    ui:[&_table]:mt-8 ui:[&_table]:block ui:[&_table]:overflow-x-auto ui:[&_table]:border-collapse ui:[&_table]:text-base
    ui:[&_th]:border-0 ui:[&_th]:border-b-2 ui:[&_th]:border-solid ui:[&_th]:border-ink ui:[&_th]:p-3 ui:[&_th]:text-left
    ui:[&_td]:border-0 ui:[&_td]:border-b ui:[&_td]:border-solid ui:[&_td]:border-line ui:[&_td]:p-3
']) }}>
  {{ $slot }}
</div>
