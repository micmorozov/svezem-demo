// Категория 107 - Аренда бетономешалки

#encoding "utf-8"
#GRAMMAR_ROOT S

Transp -> AnyWord<kwtype="аренда">;
What -> "бетономешалка";

Fact -> Transp (AnyWord+) What;

S -> Fact interp(Category.Category_107);