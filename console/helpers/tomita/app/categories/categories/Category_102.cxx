// Категория 102 - Аренда бетононасоса

#encoding "utf-8"
#GRAMMAR_ROOT S

Transp -> AnyWord<kwtype="аренда">;
What -> "бетононасос" | "автобетононасос";

Fact -> Transp (AnyWord+) What;

S -> Fact interp(Category.Category_102);